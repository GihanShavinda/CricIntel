<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreTournamentRequest;
use App\Http\Requests\Competition\SyncTournamentTeamsRequest;
use App\Http\Requests\Competition\UpdateTournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TournamentController extends Controller
{
    private function validateSeason(Organization $organization, array $data): void
    {
        $season=Season::findOrFail($data['season_id']);
        if($season->organization_id!==$organization->id) {
            throw ValidationException::withMessages(['season_id'=>'Season does not belong to this organization.']);
        }

        $start=$data['start_date'] ?? null;
        $end=$data['end_date'] ?? null;

        if($start && $end) {
            if($start < $season->start_date->toDateString() || $end > $season->end_date->toDateString()) {
                throw ValidationException::withMessages([
                    'start_date'=>'Tournament dates must fall within the selected season.'
                ]);
            }
        }
    }

    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Tournament::class, $organization]);

        $query=$organization->tournaments()->with(['season','competitionFormat'])->withCount(['teams','fixtures']);

        if($search=$request->string('search')->toString()) {
            $query->where(function($q) use($search){
                $q->where('name','ilike',"%{$search}%")
                  ->orWhere('organizer','ilike',"%{$search}%");
            });
        }

        foreach(['format','status','season_id'] as $filter) {
            if($request->filled($filter)) $query->where($filter,$request->get($filter));
        }

        $allowed=['name','format','start_date','end_date','status','created_at'];
        $sort=in_array($request->get('sort'),$allowed,true)?$request->get('sort'):'start_date';
        $direction=$request->get('direction')==='asc'?'asc':'desc';
        $perPage=min(max((int)$request->get('per_page',15),1),100);

        $rows=$query->orderBy($sort,$direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            TournamentResource::collection($rows)->response()->getData(true),
            'Tournaments retrieved successfully.'
        );
    }

    public function store(StoreTournamentRequest $request, Organization $organization): JsonResponse
    {
        $data=$request->validated();
        $this->validateSeason($organization,$data);

        $tournament=$organization->tournaments()->create($data);

        return ApiResponse::success(
            new TournamentResource($tournament->load(['season','competitionFormat','teams'])),
            'Tournament created successfully.',
            201
        );
    }

    public function show(Organization $organization, Tournament $tournament): JsonResponse
    {
        abort_unless($tournament->organization_id===$organization->id,404);
        Gate::authorize('view', $tournament);

        return ApiResponse::success(
            new TournamentResource($tournament->load(['season','competitionFormat','teams','fixtures.homeTeam','fixtures.awayTeam','fixtures.venue'])),
            'Tournament retrieved successfully.'
        );
    }

    public function update(UpdateTournamentRequest $request, Organization $organization, Tournament $tournament): JsonResponse
    {
        abort_unless($tournament->organization_id===$organization->id,404);
        $data=$request->validated();

        $merged=[
            'season_id'=>$data['season_id'] ?? $tournament->season_id,
            'start_date'=>$data['start_date'] ?? $tournament->start_date->toDateString(),
            'end_date'=>$data['end_date'] ?? $tournament->end_date->toDateString(),
        ];
        $this->validateSeason($organization,$merged);

        $tournament->update($data);

        return ApiResponse::success(
            new TournamentResource($tournament->refresh()->load(['season','competitionFormat','teams'])),
            'Tournament updated successfully.'
        );
    }

    public function syncTeams(SyncTournamentTeamsRequest $request, Organization $organization, Tournament $tournament): JsonResponse
    {
        abort_unless($tournament->organization_id===$organization->id,404);

        $payload=[];
        foreach($request->validated('teams') as $entry) {
            $team=Team::with('club')->findOrFail($entry['team_id']);
            if($team->club->organization_id!==$organization->id) {
                throw ValidationException::withMessages(['teams'=>'All teams must belong to this organization.']);
            }
            $payload[$team->id]=[
                'seed'=>$entry['seed'] ?? null,
                'status'=>$entry['status'],
            ];
        }

        $tournament->teams()->sync($payload);

        return ApiResponse::success(
            new TournamentResource($tournament->refresh()->load('teams')),
            'Tournament teams synchronized successfully.'
        );
    }

    public function destroy(Organization $organization, Tournament $tournament): JsonResponse
    {
        abort_unless($tournament->organization_id===$organization->id,404);
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return ApiResponse::success(null,'Tournament deleted successfully.');
    }
}
