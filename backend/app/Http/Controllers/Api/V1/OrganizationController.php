<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Organization::class);

        $user = $request->user();
        $query = Organization::query()->withCount(['members','clubs','seasons']);

        if (! $user->hasRole('Administrator')) {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('organization_user.status', 'active');
            });
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('short_name', 'ilike', "%{$search}%")
                  ->orWhere('country', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('country')) {
            $query->where('country', $request->string('country')->toString());
        }

        $allowedSorts = ['name','short_name','country','status','created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $paginated = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            OrganizationResource::collection($paginated)->response()->getData(true),
            'Organizations retrieved successfully.'
        );
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->service->create(
            $request->safe()->except('logo'),
            $request->user(),
            $request->file('logo')
        );

        return ApiResponse::success(
            new OrganizationResource($organization->loadCount(['members','clubs','seasons'])),
            'Organization created successfully.',
            201
        );
    }

    public function show(Organization $organization): JsonResponse
    {
        Gate::authorize('view', $organization);

        return ApiResponse::success(
            new OrganizationResource($organization->loadCount(['members','clubs','seasons'])),
            'Organization retrieved successfully.'
        );
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $organization = $this->service->update(
            $organization,
            $request->safe()->except('logo'),
            $request->file('logo')
        );

        return ApiResponse::success(
            new OrganizationResource($organization->loadCount(['members','clubs','seasons'])),
            'Organization updated successfully.'
        );
    }

    public function destroy(Organization $organization): JsonResponse
    {
        Gate::authorize('delete', $organization);
        $this->service->delete($organization);

        return ApiResponse::success(null, 'Organization deleted successfully.');
    }
}
