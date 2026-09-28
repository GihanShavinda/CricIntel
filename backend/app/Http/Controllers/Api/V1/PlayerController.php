<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\StorePlayerAvailabilityRequest;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Http\Requests\Player\SyncPlayerTeamsRequest;
use App\Http\Requests\Player\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Organization;
use App\Models\Player;
use App\Models\PlayerAvailability;
use App\Models\Team;
use App\Services\Player\PlayerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlayerController extends Controller
{
    public function __construct(private readonly PlayerService $service) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', Organization::class);

        $query = $organization->players()
            ->with(['positions','teams' => fn($q) => $q->orderByPivot('is_current','desc')]);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name','ilike',"%{$search}%")
                  ->orWhere('last_name','ilike',"%{$search}%")
                  ->orWhere('display_name','ilike',"%{$search}%")
                  ->orWhere('nationality','ilike',"%{$search}%");
            });
        }

        foreach ([
            'primary_role' => 'primary_role',
            'batting_style' => 'batting_style',
            'bowling_style' => 'bowling_style',
            'status' => 'status',
            'fitness_status' => 'fitness_status',
        ] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->get($param));
            }
        }

        if ($request->filled('team_id')) {
            $teamId = (int) $request->get('team_id');
            $query->whereHas('teams', fn($q) => $q->where('teams.id', $teamId));
        }

        if ($request->filled('availability')) {
            $availability = $request->string('availability')->toString();

            $query->whereHas('availability', function ($q) use ($availability) {
                if ($availability === 'available') {
                    $q->where('status', 'Available')
                      ->whereDate('available_from', '<=', now()->toDateString())
                      ->where(function ($sub) {
                          $sub->whereNull('available_to')
                              ->orWhereDate('available_to', '>=', now()->toDateString());
                      });
                } else {
                    $q->where('status', '!=', 'Available');
                }
            });
        }

        $allowedSorts = ['display_name','first_name','last_name','primary_role','status','created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true)
            ? $request->get('sort')
            : 'display_name';

        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int)$request->get('per_page',15),1),100);

        $players = $query->orderBy($sort,$direction)->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            PlayerResource::collection($players)->response()->getData(true),
            'Players retrieved successfully.'
        );
    }

    public function store(StorePlayerRequest $request, Organization $organization): JsonResponse
    {
        $player = $this->service->create(
            $organization,
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return ApiResponse::success(
            new PlayerResource($player->load(['positions','teams','availability'])),
            'Player created successfully.',
            201
        );
    }

    public function show(Organization $organization, Player $player): JsonResponse
    {
        abort_unless($player->organization_id === $organization->id, 404);
        Gate::authorize('view', $player);

        return ApiResponse::success(
            new PlayerResource($player->load(['positions','teams','availability','contacts','documents'])),
            'Player retrieved successfully.'
        );
    }

    public function update(UpdatePlayerRequest $request, Organization $organization, Player $player): JsonResponse
    {
        abort_unless($player->organization_id === $organization->id, 404);
        Gate::authorize('update', $player);
        $player = $this->service->update(
            $player,
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return ApiResponse::success(
            new PlayerResource($player->load(['positions','teams','availability'])),
            'Player updated successfully.'
        );
    }

    public function destroy(Organization $organization, Player $player): JsonResponse
    {
        abort_unless($player->organization_id === $organization->id, 404);
        Gate::authorize('delete', $player);

        $this->service->delete($player);

        return ApiResponse::success(null, 'Player deleted successfully.');
    }

    public function syncTeams(SyncPlayerTeamsRequest $request, Organization $organization, Player $player): JsonResponse
    {
        abort_unless($player->organization_id === $organization->id, 404);

        $payload = [];
        foreach ($request->validated('memberships') as $membership) {
            $team = Team::with('club')->findOrFail($membership['team_id']);
            abort_unless($team->club->organization_id === $organization->id, 422);

            $payload[$team->id] = [
                'jersey_number' => $membership['jersey_number'] ?? null,
                'joined_at' => $membership['joined_at'] ?? null,
                'left_at' => $membership['left_at'] ?? null,
                'is_current' => $membership['is_current'],
            ];
        }

        $player->teams()->sync($payload);

        return ApiResponse::success(
            new PlayerResource($player->refresh()->load(['positions','teams','availability'])),
            'Player team history synchronized successfully.'
        );
    }

    public function storeAvailability(
        StorePlayerAvailabilityRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        abort_unless($player->organization_id === $organization->id, 404);

        $player->availability()->create($request->validated());

        return ApiResponse::success(
            new PlayerResource($player->refresh()->load(['positions','teams','availability'])),
            'Availability added successfully.',
            201
        );
    }

    public function destroyAvailability(
        Organization $organization,
        Player $player,
        PlayerAvailability $availability
    ): JsonResponse {
        abort_unless($player->organization_id === $organization->id, 404);
        abort_unless($availability->player_id === $player->id, 404);

        Gate::authorize('update', $player);
        $availability->delete();

        return ApiResponse::success(null, 'Availability removed successfully.');
    }
}
