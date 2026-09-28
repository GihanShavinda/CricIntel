<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\SyncSeasonTeamsRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;
use App\Http\Resources\SeasonResource;
use App\Models\Organization;
use App\Models\Season;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Season::class, $organization]);

        $query = $organization->seasons()->withCount('teams');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $allowedSorts = ['name','start_date','end_date','status','created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'start_date';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $paginated = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            SeasonResource::collection($paginated)->response()->getData(true),
            'Seasons retrieved successfully.'
        );
    }

    public function store(StoreSeasonRequest $request, Organization $organization): JsonResponse
    {
        $season = $organization->seasons()->create($request->validated());

        return ApiResponse::success(
            new SeasonResource($season->loadCount('teams')),
            'Season created successfully.',
            201
        );
    }

    public function show(Organization $organization, Season $season): JsonResponse
    {
        abort_unless($season->organization_id === $organization->id, 404);
        Gate::authorize('view', $season);

        return ApiResponse::success(
            new SeasonResource($season->load(['teams.club'])),
            'Season retrieved successfully.'
        );
    }

    public function update(UpdateSeasonRequest $request, Organization $organization, Season $season): JsonResponse
    {
        abort_unless($season->organization_id === $organization->id, 404);
        $season->update($request->validated());

        return ApiResponse::success(
            new SeasonResource($season->refresh()->loadCount('teams')),
            'Season updated successfully.'
        );
    }

    public function syncTeams(SyncSeasonTeamsRequest $request, Organization $organization, Season $season): JsonResponse
    {
        abort_unless($season->organization_id === $organization->id, 404);
        $season->teams()->sync($request->validated('team_ids'));

        return ApiResponse::success(
            new SeasonResource($season->refresh()->load(['teams.club'])),
            'Season teams synchronized successfully.'
        );
    }

    public function destroy(Organization $organization, Season $season): JsonResponse
    {
        abort_unless($season->organization_id === $organization->id, 404);
        Gate::authorize('delete', $season);
        $season->delete();

        return ApiResponse::success(null, 'Season deleted successfully.');
    }
}
