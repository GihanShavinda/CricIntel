<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreVenueRequest;
use App\Http\Requests\Competition\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VenueController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Venue::class, $organization]);

        $query = $organization->venues();

        if ($search = $request->string('search')->toString()) {
            $query->where(function($q) use ($search) {
                $q->where('name','ilike',"%{$search}%")
                  ->orWhere('city','ilike',"%{$search}%")
                  ->orWhere('country','ilike',"%{$search}%");
            });
        }

        if ($request->filled('status')) $query->where('status',$request->get('status'));
        if ($request->filled('city')) $query->where('city','ilike','%'.$request->get('city').'%');

        $allowed=['name','city','country','capacity','created_at'];
        $sort=in_array($request->get('sort'),$allowed,true)?$request->get('sort'):'name';
        $direction=$request->get('direction')==='desc'?'desc':'asc';
        $perPage=min(max((int)$request->get('per_page',15),1),100);

        $rows=$query->orderBy($sort,$direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            VenueResource::collection($rows)->response()->getData(true),
            'Venues retrieved successfully.'
        );
    }

    public function store(StoreVenueRequest $request, Organization $organization): JsonResponse
    {
        $venue=$organization->venues()->create($request->validated());

        return ApiResponse::success(new VenueResource($venue),'Venue created successfully.',201);
    }

    public function show(Organization $organization, Venue $venue): JsonResponse
    {
        abort_unless($venue->organization_id===$organization->id,404);
        Gate::authorize('view', $venue);
        return ApiResponse::success(new VenueResource($venue),'Venue retrieved successfully.');
    }

    public function update(UpdateVenueRequest $request, Organization $organization, Venue $venue): JsonResponse
    {
        abort_unless($venue->organization_id===$organization->id,404);
        $venue->update($request->validated());
        return ApiResponse::success(new VenueResource($venue->refresh()),'Venue updated successfully.');
    }

    public function destroy(Organization $organization, Venue $venue): JsonResponse
    {
        abort_unless($venue->organization_id===$organization->id,404);
        Gate::authorize('delete', $venue);
        $venue->delete();
        return ApiResponse::success(null,'Venue deleted successfully.');
    }
}
