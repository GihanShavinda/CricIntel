<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Selection\{CreateMatchSquadRequest, CreateTournamentSquadRequest, SyncPlayingXiRequest, SyncSquadPlayersRequest, UpdateBattingOrderRequest, UpdateBowlingAssignmentsRequest};
use App\Models\{CricketMatch, MatchSquad, Organization, Squad, Team, Tournament};
use App\Services\Selection\{MatchSquadService, PlayingXiService, SelectionContextService, TournamentSquadService};
use Illuminate\Http\{JsonResponse, Request};

class SelectionController extends Controller
{
    public function __construct(private readonly TournamentSquadService $tournamentSquads, private readonly MatchSquadService $matchSquads, private readonly PlayingXiService $playingXi, private readonly SelectionContextService $context) {}
    private function manage(Request $r): void
    {
        $u = $r->user();
        if ($u->can('access-administration')) return;
        $ok = method_exists($u, 'hasAnyRole') && $u->hasAnyRole(['Coach', 'Selector', 'Team Manager']);
        abort_unless($ok, 403, 'Not authorized to manage selections.');
    }
    private function matchOrg(Organization $o, CricketMatch $m): void
    {
        abort_unless((int)$m->organization_id === (int)$o->id, 404);
    }
    private function squadOrg(Organization $o, Squad $s): void
    {
        abort_unless((int)$s->organization_id === (int)$o->id, 404);
    }
    private function ms(CricketMatch $m, Team $t): MatchSquad
    {
        return MatchSquad::query()->where('match_id', $m->id)->where('team_id', $t->id)->firstOrFail();
    }
    public function tournamentSquad(Organization $organization, Tournament $tournament, Team $team): JsonResponse
    {
        $s = Squad::query()->where('organization_id', $organization->id)->where('tournament_id', $tournament->id)->where('team_id', $team->id)->with(['players.player', 'team', 'tournament'])->first();
        return response()->json(['data' => $s]);
    }
    public function createTournamentSquad(CreateTournamentSquadRequest $r, Organization $organization, Tournament $tournament, Team $team): JsonResponse
    {
        $this->manage($r);
        $s = Squad::query()->updateOrCreate(['tournament_id' => $tournament->id, 'team_id' => $team->id], ['organization_id' => $organization->id, 'name' => $r->string('name')->toString(), 'min_players' => $r->integer('min_players') ?: 11, 'max_players' => $r->integer('max_players') ?: 18, 'status' => 'Draft', 'created_by' => $r->user()->id]);
        return response()->json(['data' => $s->fresh()], 201);
    }
    public function syncTournamentPlayers(SyncSquadPlayersRequest $r, Organization $organization, Squad $squad): JsonResponse
    {
        $this->manage($r);
        $this->squadOrg($organization, $squad);
        return response()->json(['data' => $this->tournamentSquads->syncPlayers($squad, $r->validated('player_ids'), $r->user()->id)]);
    }
    public function finalizeTournamentSquad(Request $r, Organization $organization, Squad $squad): JsonResponse
    {
        $this->manage($r);
        $this->squadOrg($organization, $squad);
        return response()->json(['data' => $this->tournamentSquads->finalize($squad, $r->user()->id)]);
    }
    public function matchSquad(Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->matchOrg($organization, $match);
        $ms = MatchSquad::query()->where('match_id', $match->id)->where('team_id', $team->id)->with(['players.player', 'playingXi.player', 'battingOrder.player', 'bowlingAssignments.player'])->first();
        return response()->json(['data' => $ms]);
    }
    public function saveMatchSquad(CreateMatchSquadRequest $r, Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->manage($r);
        $this->matchOrg($organization, $match);
        $s = $r->filled('tournament_squad_id') ? Squad::query()->findOrFail($r->integer('tournament_squad_id')) : null;
        return response()->json(['data' => $this->matchSquads->save($match, $team, $s, $r->validated('players'), $r->user()->id)]);
    }
    public function candidates(Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->matchOrg($organization, $match);
        $s = Squad::query()->where('organization_id', $organization->id)->where('team_id', $team->id)->latest('id')->first();
        $ps = $s ? $s->players()->whereNull('removed_at')->where('status', '!=', 'Withdrawn')->with('player')->get()->pluck('player')->filter() : collect();
        return response()->json(['data' => $ps->map(fn($p) => ['player' => $p, ...$this->context->forPlayer($p, $match)])->values()]);
    }
    public function savePlayingXi(SyncPlayingXiRequest $r, Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->manage($r);
        $this->matchOrg($organization, $match);
        return response()->json(['data' => $this->playingXi->saveDraft($this->ms($match, $team), $r->validated('players'), $r->user()->id)]);
    }
    public function confirmPlayingXi(Request $r, Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->manage($r);
        $this->matchOrg($organization, $match);
        return response()->json(['data' => $this->playingXi->confirm($this->ms($match, $team), $r->user()->id)]);
    }
    public function saveBattingOrder(UpdateBattingOrderRequest $r, Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->manage($r);
        $this->matchOrg($organization, $match);
        return response()->json(['data' => $this->playingXi->saveBattingOrder($this->ms($match, $team), $r->validated('players'), $r->user()->id)]);
    }
    public function saveBowlingAssignments(UpdateBowlingAssignmentsRequest $r, Organization $organization, CricketMatch $match, Team $team): JsonResponse
    {
        $this->manage($r);
        $this->matchOrg($organization, $match);
        return response()->json(['data' => $this->playingXi->saveBowlingAssignments($this->ms($match, $team), $r->validated('assignments'), $r->user()->id)]);
    }
}
