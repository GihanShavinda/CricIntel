<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Strategy\StrategyPlanRequest;
use App\Http\Requests\Strategy\StrategySectionRequest;
use App\Models\CricketMatch;
use App\Models\Organization;
use App\Models\StrategyPlan;
use App\Models\StrategySection;
use App\Models\Team;
use App\Models\Venue;
use App\Services\Strategy\StrategyAccessService;
use App\Services\Strategy\StrategySectionTemplateService;
use App\Services\Strategy\StrategyVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StrategyPlanController extends Controller
{
    public function __construct(
        private readonly StrategyAccessService $access,
        private readonly StrategySectionTemplateService $templates,
        private readonly StrategyVersionService $versions
    ) {}

    public function index(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        $query = StrategyPlan::query()
            ->where('organization_id', $organization->id)
            ->with([
                'opponentTeam:id,name,short_name',
                'venue:id,name,city,country,pitch_type',
                'creator:id,name',
                'updater:id,name',
            ])
            ->withCount([
                'notes',
                'assignments',
            ]);

        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';

            $query->where(function ($builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('match_id')) {
            $query->where('match_id', $request->integer('match_id'));
        }

        return response()->json([
            'data' => $query
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function store(
        StrategyPlanRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $data = $request->validated();

        $this->assertReferences($organization, $data);

        $plan = DB::transaction(function () use ($request, $organization, $data) {
            $plan = StrategyPlan::query()->create([
                'organization_id' => $organization->id,
                'match_id' => $data['match_id'],
                'opponent_team_id' => $data['opponent_team_id'] ?? null,
                'venue_id' => $data['venue_id'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'title' => $data['title'],
                'status' => $data['status'] ?? 'Draft',
                'summary' => $data['summary'] ?? null,
            ]);

            foreach ($this->templates->templates() as $template) {
                StrategySection::query()->create([
                    'strategy_plan_id' => $plan->id,
                    'updated_by' => $request->user()->id,
                    'section_key' => $template['key'],
                    'title' => $template['title'],
                    'sort_order' => $template['sort_order'],
                ]);
            }

            $this->versions->record(
                $plan,
                $request->user(),
                'plan.created',
                'strategy_plan',
                $plan->id,
                'Strategy plan created.',
                null,
                $plan
            );

            return $plan;
        });

        return response()->json([
            'message' => 'Strategy plan created successfully.',
            'data' => $this->workspace($plan),
        ], 201);
    }

    public function show(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertView($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        return response()->json([
            'data' => $this->workspace($strategyPlan),
        ]);
    }

    public function update(
        StrategyPlanRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_if(
            $strategyPlan->locked_at,
            422,
            'This strategy plan is locked.'
        );

        $data = $request->validated();
        $this->assertReferences($organization, $data);

        $before = $strategyPlan->toArray();

        $strategyPlan->update([
            'match_id' => $data['match_id'],
            'opponent_team_id' => $data['opponent_team_id'] ?? null,
            'venue_id' => $data['venue_id'] ?? null,
            'updated_by' => $request->user()->id,
            'title' => $data['title'],
            'status' => $data['status'] ?? $strategyPlan->status,
            'summary' => $data['summary'] ?? null,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'plan.updated',
            'strategy_plan',
            $strategyPlan->id,
            'Strategy plan details updated.',
            $before,
            $strategyPlan->fresh()
        );

        return response()->json([
            'message' => 'Strategy plan updated successfully.',
            'data' => $this->workspace($strategyPlan->fresh()),
        ]);
    }

    public function destroy(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_if(
            $strategyPlan->locked_at,
            422,
            'Unlock the strategy plan before deleting it.'
        );

        $before = $this->versions->workspaceSnapshot($strategyPlan);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'plan.deleted',
            'strategy_plan',
            $strategyPlan->id,
            'Strategy plan deleted.',
            $before,
            null
        );

        $strategyPlan->delete();

        return response()->json([
            'message' => 'Strategy plan deleted successfully.',
        ]);
    }

    public function updateSection(
        StrategySectionRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        StrategySection $strategySection
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_unless(
            (int) $strategySection->strategy_plan_id === (int) $strategyPlan->id,
            404
        );

        abort_if(
            $strategyPlan->locked_at,
            422,
            'This strategy plan is locked.'
        );

        $before = $strategySection->toArray();

        $strategySection->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $strategyPlan->update([
            'updated_by' => $request->user()->id,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'section.updated',
            'strategy_section',
            $strategySection->id,
            "{$strategySection->title} updated.",
            $before,
            $strategySection->fresh()
        );

        return response()->json([
            'message' => 'Strategy section updated.',
            'data' => $strategySection->fresh()->load('updater:id,name'),
        ]);
    }

    public function versions(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertView($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        return response()->json([
            'data' => $strategyPlan->versions()
                ->with('actor:id,name,email')
                ->limit(100)
                ->get(),
        ]);
    }

    public function lock(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        $before = $strategyPlan->toArray();

        $strategyPlan->update([
            'locked_at' => now(),
            'locked_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'plan.locked',
            'strategy_plan',
            $strategyPlan->id,
            'Strategy plan locked.',
            $before,
            $strategyPlan->fresh()
        );

        return response()->json([
            'data' => $this->workspace($strategyPlan->fresh()),
        ]);
    }

    public function unlock(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        $before = $strategyPlan->toArray();

        $strategyPlan->update([
            'locked_at' => null,
            'locked_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'plan.unlocked',
            'strategy_plan',
            $strategyPlan->id,
            'Strategy plan unlocked.',
            $before,
            $strategyPlan->fresh()
        );

        return response()->json([
            'data' => $this->workspace($strategyPlan->fresh()),
        ]);
    }

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        $matches = DB::table('matches as m')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('teams as home', 'home.id', '=', 'f.home_team_id')
            ->join('teams as away', 'away.id', '=', 'f.away_team_id')
            ->leftJoin('venues as v', 'v.id', '=', 'f.venue_id')
            ->where('m.organization_id', $organization->id)
            ->orderByDesc('f.scheduled_at')
            ->get([
                'm.id',
                'm.status',
                'f.scheduled_at',
                'f.home_team_id',
                'home.name as home_team_name',
                'f.away_team_id',
                'away.name as away_team_name',
                'f.venue_id',
                'v.name as venue_name',
            ]);

        $teams = DB::table('teams as t')
            ->join('clubs as c', 'c.id', '=', 't.club_id')
            ->where('c.organization_id', $organization->id)
            ->orderBy('t.name')
            ->get(['t.id', 't.name', 't.short_name']);

        $venues = DB::table('venues')
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'country', 'pitch_type']);

        $players = DB::table('players')
            ->where('organization_id', $organization->id)
            ->orderBy('display_name')
            ->get([
                'id',
                'display_name',
                'primary_role',
                'fitness_status',
                'status',
                'batting_style',
                'bowling_style',
            ]);

        $collaboratorRows = DB::table('users as u')
            ->join('organization_user as ou', 'ou.user_id', '=', 'u.id')
            ->leftJoin('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ou.organization_id', $organization->id)
            ->where('ou.status', 'active')
            ->whereIn('r.name', [
                'Administrator',
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ])
            ->orderBy('u.name')
            ->get([
                'u.id',
                'u.name',
                'u.email',
                'r.name as role',
            ]);

        $collaborators = $collaboratorRows
            ->groupBy('id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'id' => $first->id,
                    'name' => $first->name,
                    'email' => $first->email,
                    'roles' => $rows->pluck('role')->filter()->unique()->values(),
                ];
            })
            ->values();

        $scoutingReports = DB::table('scouting_reports as sr')
            ->join('scouting_profiles as sp', 'sp.id', '=', 'sr.scouting_profile_id')
            ->where('sp.organization_id', $organization->id)
            ->orderByDesc('sr.report_date')
            ->limit(200)
            ->get([
                'sr.id',
                'sr.report_date',
                'sr.competition',
                'sr.overall_recommendation',
                'sp.id as profile_id',
                'sp.display_name',
            ]);

        $scoutingMedia = DB::table('scouting_media as sm')
            ->join('scouting_reports as sr', 'sr.id', '=', 'sm.scouting_report_id')
            ->join('scouting_profiles as sp', 'sp.id', '=', 'sr.scouting_profile_id')
            ->where('sp.organization_id', $organization->id)
            ->orderByDesc('sm.created_at')
            ->limit(200)
            ->get([
                'sm.id',
                'sm.media_type',
                'sm.video_url',
                'sm.original_filename',
                'sm.caption',
                'sp.display_name',
            ]);

        return response()->json([
            'data' => [
                'matches' => $matches,
                'teams' => $teams,
                'venues' => $venues,
                'players' => $players,
                'collaborators' => $collaborators,
                'scouting_reports' => $scoutingReports,
                'scouting_media' => $scoutingMedia,
            ],
        ]);
    }

    private function workspace(StrategyPlan $plan): StrategyPlan
    {
        return $plan->load([
            'opponentTeam:id,name,short_name',
            'venue:id,name,city,country,pitch_type',
            'creator:id,name,email',
            'updater:id,name,email',
            'locker:id,name',
            'sections.updater:id,name',
            'notes' => fn ($query) => $query
                ->with([
                    'section:id,title,section_key',
                    'author:id,name,email',
                    'resolver:id,name,email',
                    'comments.author:id,name,email',
                ])
                ->orderByDesc('created_at'),
            'attachments.uploader:id,name,email',
            'assignments' => fn ($query) => $query
                ->with([
                    'section:id,title,section_key',
                    'assignee:id,name,email',
                    'assigner:id,name,email',
                ])
                ->orderBy('status')
                ->orderBy('due_at'),
            'mentions' => fn ($query) => $query
                ->with([
                    'mentionedUser:id,name,email',
                    'mentioner:id,name,email',
                ])
                ->orderByDesc('created_at'),
            'versions' => fn ($query) => $query
                ->with('actor:id,name,email')
                ->limit(30),
        ]);
    }

    private function assertReferences(
        Organization $organization,
        array $data
    ): void {
        $matchValid = CricketMatch::query()
            ->whereKey($data['match_id'])
            ->where('organization_id', $organization->id)
            ->exists();

        if (! $matchValid) {
            throw ValidationException::withMessages([
                'match_id' => 'The match must belong to this organization.',
            ]);
        }

        if (! empty($data['venue_id'])) {
            $venueValid = Venue::query()
                ->whereKey($data['venue_id'])
                ->where('organization_id', $organization->id)
                ->exists();

            if (! $venueValid) {
                throw ValidationException::withMessages([
                    'venue_id' => 'The venue must belong to this organization.',
                ]);
            }
        }

        if (! empty($data['opponent_team_id'])) {
            $teamValid = Team::query()
                ->whereKey($data['opponent_team_id'])
                ->whereHas('club', fn ($query) =>
                    $query->where('organization_id', $organization->id)
                )
                ->exists();

            if (! $teamValid) {
                throw ValidationException::withMessages([
                    'opponent_team_id' => 'The opponent team must belong to this organization.',
                ]);
            }
        }
    }
}
