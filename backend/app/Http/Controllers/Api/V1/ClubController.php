<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreClubRequest;
use App\Http\Requests\Club\UpdateClubRequest;
use App\Http\Resources\ClubResource;
use App\Models\Club;
use App\Models\Organization;
use App\Services\Organization\ClubService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClubController extends Controller
{
    public function __construct(private readonly ClubService $service) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Club::class, $organization]);

        $query = $organization->clubs()->withCount('teams');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'ilike', '%'.$request->string('location')->toString().'%');
        }

        $allowedSorts = ['name','code','location','founded_year','created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $paginated = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            ClubResource::collection($paginated)->response()->getData(true),
            'Clubs retrieved successfully.'
        );
    }

    public function store(StoreClubRequest $request, Organization $organization): JsonResponse
    {
        $club = $this->service->create(
            $organization,
            $request->safe()->except('logo'),
            $request->file('logo')
        );

        return ApiResponse::success(
            new ClubResource($club->loadCount('teams')),
            'Club created successfully.',
            201
        );
    }

    public function show(Organization $organization, Club $club): JsonResponse
    {
        abort_unless($club->organization_id === $organization->id, 404);
        Gate::authorize('view', $club);

        return ApiResponse::success(new ClubResource($club->loadCount('teams')), 'Club retrieved successfully.');
    }

    public function update(UpdateClubRequest $request, Organization $organization, Club $club): JsonResponse
    {
        abort_unless($club->organization_id === $organization->id, 404);

        $club = $this->service->update(
            $club,
            $request->safe()->except('logo'),
            $request->file('logo')
        );

        return ApiResponse::success(new ClubResource($club->loadCount('teams')), 'Club updated successfully.');
    }

    public function destroy(Organization $organization, Club $club): JsonResponse
    {
        abort_unless($club->organization_id === $organization->id, 404);
        Gate::authorize('delete', $club);
        $this->service->delete($club);

        return ApiResponse::success(null, 'Club deleted successfully.');
    }
}
