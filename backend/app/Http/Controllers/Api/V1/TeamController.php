<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Organization;
use App\Models\Team;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Team::class, $organization]);

        $query = Team::query()
            ->with('club')
            ->whereHas('club', fn ($q) => $q->where('organization_id', $organization->id));

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('short_name', 'ilike', "%{$search}%")
                  ->orWhere('home_ground', 'ilike', "%{$search}%");
            });
        }

        foreach (['status','gender','category','age_group','club_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->get($filter));
            }
        }

        $allowedSorts = ['name','short_name','gender','category','age_group','status','created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $paginated = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            TeamResource::collection($paginated)->response()->getData(true),
            'Teams retrieved successfully.'
        );
    }

    public function store(StoreTeamRequest $request, Organization $organization): JsonResponse
    {
        $team = Team::create($request->validated());

        return ApiResponse::success(
            new TeamResource($team->load('club')),
            'Team created successfully.',
            201
        );
    }

    public function show(Organization $organization, Team $team): JsonResponse
    {
        $team->load(['club.organization','seasons']);
        abort_unless($team->club->organization_id === $organization->id, 404);
        Gate::authorize('viewAny', [Team::class, $organization]);

        return ApiResponse::success(new TeamResource($team), 'Team retrieved successfully.');
    }

    public function update(UpdateTeamRequest $request, Organization $organization, Team $team): JsonResponse
    {
        $team->load('club.organization');
        abort_unless($team->club->organization_id === $organization->id, 404);

        $team->update($request->validated());

        return ApiResponse::success(
            new TeamResource($team->refresh()->load('club')),
            'Team updated successfully.'
        );
    }

    public function destroy(Organization $organization, Team $team): JsonResponse
    {
        $team->load('club.organization');
        abort_unless($team->club->organization_id === $organization->id, 404);
        Gate::authorize('viewAny', [Team::class, $organization]);
        $team->delete();

        return ApiResponse::success(null, 'Team deleted successfully.');
    }
}
