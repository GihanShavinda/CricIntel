<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreFixtureRequest;
use App\Http\Requests\Competition\UpdateFixtureRequest;
use App\Http\Resources\FixtureResource;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Venue;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class FixtureController extends Controller
{
    private function validateFixture(Organization $organization, array $data, ?Fixture $ignore=null): void
    {
        $tournament=Tournament::findOrFail($data['tournament_id']);

        if($tournament->organization_id!==$organization->id) {
            throw ValidationException::withMessages(['tournament_id'=>'Tournament does not belong to this organization.']);
        }

        foreach(['home_team_id','away_team_id'] as $key) {
            $team=Team::with('club')->findOrFail($data[$key]);
            if($team->club->organization_id!==$organization->id) {
                throw ValidationException::withMessages([$key=>'Team does not belong to this organization.']);
            }
            if(! $tournament->teams()->where('teams.id',$team->id)->exists()) {
                throw ValidationException::withMessages([$key=>'Team is not registered in this tournament.']);
            }
        }

        if(!empty($data['venue_id'])) {
            $venue=Venue::findOrFail($data['venue_id']);
            if($venue->organization_id!==$organization->id) {
                throw ValidationException::withMessages(['venue_id'=>'Venue does not belong to this organization.']);
            }
        }

        $scheduled=date('Y-m-d',strtotime($data['scheduled_at']));
        if($scheduled < $tournament->start_date->toDateString() || $scheduled > $tournament->end_date->toDateString()) {
            throw ValidationException::withMessages(['scheduled_at'=>'Fixture must be scheduled within tournament dates.']);
        }

        $duplicate=Fixture::query()
            ->where('tournament_id',$tournament->id)
            ->where('scheduled_at',$data['scheduled_at'])
            ->where(function($q) use($data){
                $q->where(function($a) use($data){
                    $a->where('home_team_id',$data['home_team_id'])->where('away_team_id',$data['away_team_id']);
                })->orWhere(function($a) use($data){
                    $a->where('home_team_id',$data['away_team_id'])->where('away_team_id',$data['home_team_id']);
                });
            });

        if($ignore) $duplicate->where('id','!=',$ignore->id);

        if($duplicate->exists()) {
            throw ValidationException::withMessages(['scheduled_at'=>'This fixture already exists for the same teams and time.']);
        }
    }

    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny',[Fixture::class,$organization]);

        $query=$organization->fixtures()->with(['tournament','homeTeam','awayTeam','venue']);

        if($request->filled('tournament_id')) $query->where('tournament_id',$request->get('tournament_id'));
        if($request->filled('venue_id')) $query->where('venue_id',$request->get('venue_id'));
        if($request->filled('status')) $query->where('status',$request->get('status'));
        if($request->filled('date_from')) $query->whereDate('scheduled_at','>=',$request->get('date_from'));
        if($request->filled('date_to')) $query->whereDate('scheduled_at','<=',$request->get('date_to'));
        if($request->filled('team_id')) {
            $teamId=(int)$request->get('team_id');
            $query->where(fn($q)=>$q->where('home_team_id',$teamId)->orWhere('away_team_id',$teamId));
        }

        $allowed=['scheduled_at','match_number','status','created_at'];
        $sort=in_array($request->get('sort'),$allowed,true)?$request->get('sort'):'scheduled_at';
        $direction=$request->get('direction')==='desc'?'desc':'asc';
        $perPage=min(max((int)$request->get('per_page',50),1),100);

        $rows=$query->orderBy($sort,$direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            FixtureResource::collection($rows)->response()->getData(true),
            'Fixtures retrieved successfully.'
        );
    }

    public function store(StoreFixtureRequest $request, Organization $organization): JsonResponse
    {
        $data=$request->validated();
        $this->validateFixture($organization,$data);

        $fixture=$organization->fixtures()->create($data);

        return ApiResponse::success(
            new FixtureResource($fixture->load(['tournament','homeTeam','awayTeam','venue'])),
            'Fixture created successfully.',
            201
        );
    }

    public function show(Organization $organization, Fixture $fixture): JsonResponse
    {
        abort_unless($fixture->organization_id===$organization->id,404);
        Gate::authorize('view',$fixture);

        return ApiResponse::success(
            new FixtureResource($fixture->load(['tournament','homeTeam','awayTeam','venue'])),
            'Fixture retrieved successfully.'
        );
    }

    public function update(UpdateFixtureRequest $request, Organization $organization, Fixture $fixture): JsonResponse
    {
        abort_unless($fixture->organization_id===$organization->id,404);

        $data=$request->validated();
        $merged=[
            'tournament_id'=>$data['tournament_id'] ?? $fixture->tournament_id,
            'home_team_id'=>$data['home_team_id'] ?? $fixture->home_team_id,
            'away_team_id'=>$data['away_team_id'] ?? $fixture->away_team_id,
            'venue_id'=>array_key_exists('venue_id',$data)?$data['venue_id']:$fixture->venue_id,
            'scheduled_at'=>$data['scheduled_at'] ?? $fixture->scheduled_at->format('Y-m-d H:i:sP'),
        ];

        $this->validateFixture($organization,$merged,$fixture);
        $fixture->update($data);

        return ApiResponse::success(
            new FixtureResource($fixture->refresh()->load(['tournament','homeTeam','awayTeam','venue'])),
            'Fixture updated successfully.'
        );
    }

    public function destroy(Organization $organization, Fixture $fixture): JsonResponse
    {
        abort_unless($fixture->organization_id===$organization->id,404);
        Gate::authorize('delete',$fixture);
        $fixture->delete();

        return ApiResponse::success(null,'Fixture deleted successfully.');
    }
}
