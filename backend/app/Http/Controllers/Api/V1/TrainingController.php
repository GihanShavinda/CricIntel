<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\AttendanceRequest;
use App\Http\Requests\Training\DevelopmentPlanRequest;
use App\Http\Requests\Training\FitnessTestRequest;
use App\Http\Requests\Training\PlayerAssessmentRequest;
use App\Http\Requests\Training\SessionPlayersRequest;
use App\Http\Requests\Training\TrainingDrillRequest;
use App\Http\Requests\Training\TrainingObjectiveRequest;
use App\Http\Requests\Training\TrainingSessionRequest;
use App\Models\Attendance;
use App\Models\DevelopmentPlan;
use App\Models\FitnessTest;
use App\Models\Organization;
use App\Models\Player;
use App\Models\PlayerAssessment;
use App\Models\Team;
use App\Models\TrainingDrill;
use App\Models\TrainingObjective;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPlayer;
use App\Services\Training\TrainingStatisticsLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingController extends Controller
{
    public function __construct(
        private readonly TrainingStatisticsLinkService $statisticsLink
    ) {}

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $teams = Team::query()
            ->whereHas('club', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $players = Player::query()
            ->where('organization_id', $organization->id)
            ->where('status', '!=', 'inactive')
            ->orderBy('display_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'display_name',
                'primary_role',
                'fitness_status',
            ]);

        $drills = TrainingDrill::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $coaches = collect();

        if (method_exists($organization, 'members')) {
            $coaches = $organization
                ->members()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', [
                        'Administrator',
                        'Coach',
                        'Team Manager',
                    ]);
                })
                ->orderBy('name')
                ->get([
                    'users.id',
                    'users.name',
                    'users.email',
                ]);
        }

        if ($coaches->isEmpty() && $request->user()) {
            $coaches = collect([
                [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'teams' => $teams,
                'players' => $players,
                'drills' => $drills,
                'coaches' => $coaches->values(),
            ],
        ]);
    }

    public function sessions(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $query = TrainingSession::query()
            ->where('organization_id', $organization->id)
            ->with([
                'team:id,name,short_name',
                'coach:id,name,email',
                'drills',
            ]);

        if ($request->filled('month')) {
            $month = $request->string('month')->toString();

            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw ValidationException::withMessages([
                    'month' => 'Month must use YYYY-MM format.',
                ]);
            }

            $query
                ->whereYear('session_date', (int) substr($month, 0, 4))
                ->whereMonth('session_date', (int) substr($month, 5, 2));
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        return response()->json([
            'data' => $query
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    public function storeSession(
        TrainingSessionRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $data = $request->validated();

        $this->assertTeamOrganization(
            $organization,
            (int) $data['team_id']
        );

        $session = DB::transaction(function () use ($request, $organization, $data) {
            $session = TrainingSession::query()->create([
                'organization_id' => $organization->id,
                'team_id' => $data['team_id'],
                'coach_id' => $data['coach_id'] ?? $request->user()->id,
                'session_date' => $data['session_date'],
                'start_time' => $data['start_time'] ?? null,
                'location' => $data['location'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'session_type' => $data['session_type'],
                'status' => $data['status'] ?? 'Scheduled',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncDrills(
                $organization,
                $session,
                $data['drill_ids'] ?? []
            );

            return $session;
        });

        return response()->json([
            'message' => 'Training session created successfully.',
            'data' => $this->loadSession($session),
        ], 201);
    }

    public function showSession(
        Organization $organization,
        TrainingSession $trainingSession
    ): JsonResponse {
        $this->assertSessionOrganization($organization, $trainingSession);

        return response()->json([
            'data' => $this->loadSession($trainingSession),
        ]);
    }

    public function updateSession(
        TrainingSessionRequest $request,
        Organization $organization,
        TrainingSession $trainingSession
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertSessionOrganization($organization, $trainingSession);

        $data = $request->validated();

        $this->assertTeamOrganization(
            $organization,
            (int) $data['team_id']
        );

        DB::transaction(function () use ($organization, $trainingSession, $data) {
            $trainingSession->update([
                'team_id' => $data['team_id'],
                'coach_id' => $data['coach_id'] ?? null,
                'session_date' => $data['session_date'],
                'start_time' => $data['start_time'] ?? null,
                'location' => $data['location'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'session_type' => $data['session_type'],
                'status' => $data['status'] ?? 'Scheduled',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncDrills(
                $organization,
                $trainingSession,
                $data['drill_ids'] ?? []
            );
        });

        return response()->json([
            'message' => 'Training session updated successfully.',
            'data' => $this->loadSession($trainingSession->fresh()),
        ]);
    }

    public function destroySession(
        Request $request,
        Organization $organization,
        TrainingSession $trainingSession
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertSessionOrganization($organization, $trainingSession);

        $trainingSession->delete();

        return response()->json([
            'message' => 'Training session deleted successfully.',
        ]);
    }

    public function drills(
        Organization $organization
    ): JsonResponse {
        return response()->json([
            'data' => TrainingDrill::query()
                ->where('organization_id', $organization->id)
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeDrill(
        TrainingDrillRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);

        $data = $request->validated();

        $drill = TrainingDrill::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Training drill created successfully.',
            'data' => $drill,
        ], 201);
    }

    public function updateDrill(
        TrainingDrillRequest $request,
        Organization $organization,
        TrainingDrill $trainingDrill
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertDrillOrganization($organization, $trainingDrill);

        $data = $request->validated();

        $trainingDrill->update([
            ...$data,
            'is_active' => $data['is_active'] ?? $trainingDrill->is_active,
        ]);

        return response()->json([
            'message' => 'Training drill updated successfully.',
            'data' => $trainingDrill->fresh(),
        ]);
    }

    public function destroyDrill(
        Request $request,
        Organization $organization,
        TrainingDrill $trainingDrill
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertDrillOrganization($organization, $trainingDrill);

        $trainingDrill->delete();

        return response()->json([
            'message' => 'Training drill deleted successfully.',
        ]);
    }

    public function syncSessionPlayers(
        SessionPlayersRequest $request,
        Organization $organization,
        TrainingSession $trainingSession
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertSessionOrganization($organization, $trainingSession);

        $playerIds = collect($request->validated('player_ids'))
            ->map(fn ($id) => (int) $id)
            ->values();

        $validCount = Player::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $playerIds)
            ->count();

        if ($validCount !== $playerIds->count()) {
            throw ValidationException::withMessages([
                'player_ids' => 'Every selected player must belong to the organization.',
            ]);
        }

        DB::transaction(function () use ($trainingSession, $playerIds) {
            $trainingSession
                ->sessionPlayers()
                ->whereNotIn('player_id', $playerIds->all() ?: [-1])
                ->delete();

            foreach ($playerIds as $playerId) {
                TrainingSessionPlayer::query()->firstOrCreate([
                    'training_session_id' => $trainingSession->id,
                    'player_id' => $playerId,
                ]);
            }
        });

        return response()->json([
            'message' => 'Training session players updated successfully.',
            'data' => $this->loadSession($trainingSession->fresh()),
        ]);
    }

    public function markAttendance(
        AttendanceRequest $request,
        Organization $organization,
        TrainingSession $trainingSession
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertSessionOrganization($organization, $trainingSession);

        DB::transaction(function () use ($request, $trainingSession) {
            foreach ($request->validated('entries') as $entry) {
                $sessionPlayer = TrainingSessionPlayer::query()
                    ->where('training_session_id', $trainingSession->id)
                    ->where('player_id', $entry['player_id'])
                    ->first();

                if (! $sessionPlayer) {
                    throw ValidationException::withMessages([
                        'entries' =>
                            "Player {$entry['player_id']} is not assigned to this training session.",
                    ]);
                }

                Attendance::query()->updateOrCreate(
                    [
                        'training_session_player_id' => $sessionPlayer->id,
                    ],
                    [
                        'status' => $entry['status'],
                        'arrival_time' => $entry['arrival_time'] ?? null,
                        'notes' => $entry['notes'] ?? null,
                        'marked_by' => $request->user()->id,
                        'marked_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Attendance saved successfully.',
            'data' => $this->loadSession($trainingSession->fresh()),
        ]);
    }

    public function fitnessTests(
        Request $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertPlayerOrganization($organization, $player);

        $query = FitnessTest::query()
            ->where('organization_id', $organization->id)
            ->where('player_id', $player->id)
            ->with([
                'trainingSession:id,session_date,session_type',
                'recordedBy:id,name',
            ]);

        if ($request->filled('test_type')) {
            $query->where(
                'test_type',
                $request->string('test_type')->toString()
            );
        }

        return response()->json([
            'data' => $query
                ->orderBy('tested_at')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function storeFitnessTest(
        FitnessTestRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $data = $request->validated();

        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);

        if (! empty($data['training_session_id'])) {
            $session = TrainingSession::query()->findOrFail($data['training_session_id']);
            $this->assertSessionOrganization($organization, $session);
        }

        $test = FitnessTest::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Fitness test recorded successfully.',
            'data' => $test->fresh([
                'player',
                'trainingSession',
                'recordedBy',
            ]),
        ], 201);
    }

    public function updateFitnessTest(
        FitnessTestRequest $request,
        Organization $organization,
        FitnessTest $fitnessTest
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertFitnessTestOrganization($organization, $fitnessTest);

        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);

        if (! empty($data['training_session_id'])) {
            $session = TrainingSession::query()->findOrFail($data['training_session_id']);
            $this->assertSessionOrganization($organization, $session);
        }

        $fitnessTest->update($data);

        return response()->json([
            'message' => 'Fitness test updated successfully.',
            'data' => $fitnessTest->fresh(),
        ]);
    }

    public function destroyFitnessTest(
        Request $request,
        Organization $organization,
        FitnessTest $fitnessTest
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertFitnessTestOrganization($organization, $fitnessTest);

        $fitnessTest->delete();

        return response()->json([
            'message' => 'Fitness test deleted successfully.',
        ]);
    }

    public function assessments(
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertPlayerOrganization($organization, $player);

        return response()->json([
            'data' => PlayerAssessment::query()
                ->where('organization_id', $organization->id)
                ->where('player_id', $player->id)
                ->with([
                    'coach:id,name',
                    'trainingSession:id,session_date,session_type',
                ])
                ->orderByDesc('assessed_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function storeAssessment(
        PlayerAssessmentRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $data = $request->validated();

        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);

        if (! empty($data['training_session_id'])) {
            $session = TrainingSession::query()->findOrFail($data['training_session_id']);
            $this->assertSessionOrganization($organization, $session);
        }

        $assessment = PlayerAssessment::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'coach_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Player assessment created successfully.',
            'data' => $assessment->fresh([
                'player',
                'coach',
                'trainingSession',
            ]),
        ], 201);
    }

    public function updateAssessment(
        PlayerAssessmentRequest $request,
        Organization $organization,
        PlayerAssessment $playerAssessment
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertAssessmentOrganization($organization, $playerAssessment);

        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);

        if (! empty($data['training_session_id'])) {
            $session = TrainingSession::query()->findOrFail($data['training_session_id']);
            $this->assertSessionOrganization($organization, $session);
        }

        $playerAssessment->update($data);

        return response()->json([
            'message' => 'Player assessment updated successfully.',
            'data' => $playerAssessment->fresh(),
        ]);
    }

    public function destroyAssessment(
        Request $request,
        Organization $organization,
        PlayerAssessment $playerAssessment
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertAssessmentOrganization($organization, $playerAssessment);

        $playerAssessment->delete();

        return response()->json([
            'message' => 'Player assessment deleted successfully.',
        ]);
    }

    public function objectives(
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertPlayerOrganization($organization, $player);

        return response()->json([
            'data' => TrainingObjective::query()
                ->where('organization_id', $organization->id)
                ->where('player_id', $player->id)
                ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
                ->orderBy('target_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function storeObjective(
        TrainingObjectiveRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);

        $player = Player::query()->findOrFail(
            $request->integer('player_id')
        );

        $this->assertPlayerOrganization($organization, $player);

        $objective = $this->statisticsLink->createObjective(
            $organization->id,
            $player,
            $request->user()->id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Training objective created successfully.',
            'data' => $objective,
        ], 201);
    }

    public function updateObjective(
        TrainingObjectiveRequest $request,
        Organization $organization,
        TrainingObjective $trainingObjective
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertObjectiveOrganization($organization, $trainingObjective);

        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);

        $trainingObjective->update($data);

        return response()->json([
            'message' => 'Training objective updated successfully.',
            'data' => $trainingObjective->fresh(),
        ]);
    }

    public function destroyObjective(
        Request $request,
        Organization $organization,
        TrainingObjective $trainingObjective
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertObjectiveOrganization($organization, $trainingObjective);

        $trainingObjective->delete();

        return response()->json([
            'message' => 'Training objective deleted successfully.',
        ]);
    }

    public function developmentPlans(
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertPlayerOrganization($organization, $player);

        return response()->json([
            'data' => DevelopmentPlan::query()
                ->where('organization_id', $organization->id)
                ->where('player_id', $player->id)
                ->with('coach:id,name')
                ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
                ->orderByDesc('start_date')
                ->get(),
        ]);
    }

    public function storeDevelopmentPlan(
        DevelopmentPlanRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $data = $request->validated();

        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);
        $this->validatePlanReferences($organization, $player, $data);

        $plan = DevelopmentPlan::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'coach_id' => $request->user()->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        return response()->json([
            'message' => 'Development plan created successfully.',
            'data' => $plan->fresh(['player', 'coach']),
        ], 201);
    }

    public function updateDevelopmentPlan(
        DevelopmentPlanRequest $request,
        Organization $organization,
        DevelopmentPlan $developmentPlan
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertPlanOrganization($organization, $developmentPlan);

        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertPlayerOrganization($organization, $player);
        $this->validatePlanReferences($organization, $player, $data);

        $developmentPlan->update($data);

        return response()->json([
            'message' => 'Development plan updated successfully.',
            'data' => $developmentPlan->fresh(),
        ]);
    }

    public function destroyDevelopmentPlan(
        Request $request,
        Organization $organization,
        DevelopmentPlan $developmentPlan
    ): JsonResponse {
        $this->assertTrainingManager($request);
        $this->assertPlanOrganization($organization, $developmentPlan);

        $developmentPlan->delete();

        return response()->json([
            'message' => 'Development plan deleted successfully.',
        ]);
    }

    private function loadSession(
        TrainingSession $session
    ): TrainingSession {
        return $session->load([
            'team:id,name,short_name',
            'coach:id,name,email',
            'drills',
            'sessionPlayers.player:id,first_name,last_name,display_name,primary_role,fitness_status',
            'sessionPlayers.attendance',
            'fitnessTests.player:id,display_name,first_name,last_name',
            'assessments.player:id,display_name,first_name,last_name',
        ]);
    }

    private function syncDrills(
        Organization $organization,
        TrainingSession $session,
        array $drillIds
    ): void {
        $ids = collect($drillIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $session->drills()->sync([]);
            return;
        }

        $validCount = TrainingDrill::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->count();

        if ($validCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'drill_ids' => 'Every drill must belong to the organization.',
            ]);
        }

        $sync = [];

        foreach ($ids as $index => $id) {
            $sync[$id] = [
                'sequence' => $index + 1,
            ];
        }

        $session->drills()->sync($sync);
    }

    private function validatePlanReferences(
        Organization $organization,
        Player $player,
        array $data
    ): void {
        $objectiveIds = collect($data['objective_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($objectiveIds->isNotEmpty()) {
            $validObjectives = TrainingObjective::query()
                ->where('organization_id', $organization->id)
                ->where('player_id', $player->id)
                ->whereIn('id', $objectiveIds)
                ->count();

            if ($validObjectives !== $objectiveIds->count()) {
                throw ValidationException::withMessages([
                    'objective_ids' =>
                        'Development plan objectives must belong to the selected player.',
                ]);
            }
        }

        $drillIds = collect($data['drill_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($drillIds->isNotEmpty()) {
            $validDrills = TrainingDrill::query()
                ->where('organization_id', $organization->id)
                ->whereIn('id', $drillIds)
                ->count();

            if ($validDrills !== $drillIds->count()) {
                throw ValidationException::withMessages([
                    'drill_ids' =>
                        'Development plan drills must belong to the organization.',
                ]);
            }
        }
    }

    private function assertTrainingManager(
        Request $request
    ): void {
        $user = $request->user();

        if ($user->can('access-administration')) {
            return;
        }

        $allowed = false;

        if (method_exists($user, 'hasAnyRole')) {
            $allowed = $user->hasAnyRole([
                'Coach',
                'Team Manager',
            ]);
        }

        abort_unless(
            $allowed,
            403,
            'You are not authorized to manage training.'
        );
    }

    private function assertTeamOrganization(
        Organization $organization,
        int $teamId
    ): void {
        $belongs = Team::query()
            ->whereKey($teamId)
            ->whereHas('club', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->exists();

        abort_unless(
            $belongs,
            404,
            'Team not found in this organization.'
        );
    }

    private function assertPlayerOrganization(
        Organization $organization,
        Player $player
    ): void {
        abort_unless(
            (int) $player->organization_id === (int) $organization->id,
            404,
            'Player not found in this organization.'
        );
    }

    private function assertSessionOrganization(
        Organization $organization,
        TrainingSession $session
    ): void {
        abort_unless(
            (int) $session->organization_id === (int) $organization->id,
            404,
            'Training session not found in this organization.'
        );
    }

    private function assertDrillOrganization(
        Organization $organization,
        TrainingDrill $drill
    ): void {
        abort_unless(
            (int) $drill->organization_id === (int) $organization->id,
            404,
            'Training drill not found in this organization.'
        );
    }

    private function assertFitnessTestOrganization(
        Organization $organization,
        FitnessTest $fitnessTest
    ): void {
        abort_unless(
            (int) $fitnessTest->organization_id === (int) $organization->id,
            404,
            'Fitness test not found in this organization.'
        );
    }

    private function assertAssessmentOrganization(
        Organization $organization,
        PlayerAssessment $assessment
    ): void {
        abort_unless(
            (int) $assessment->organization_id === (int) $organization->id,
            404,
            'Assessment not found in this organization.'
        );
    }

    private function assertObjectiveOrganization(
        Organization $organization,
        TrainingObjective $objective
    ): void {
        abort_unless(
            (int) $objective->organization_id === (int) $organization->id,
            404,
            'Training objective not found in this organization.'
        );
    }

    private function assertPlanOrganization(
        Organization $organization,
        DevelopmentPlan $plan
    ): void {
        abort_unless(
            (int) $plan->organization_id === (int) $organization->id,
            404,
            'Development plan not found in this organization.'
        );
    }
}
