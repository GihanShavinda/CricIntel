<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scouting\ConvertScoutingProfileRequest;
use App\Http\Requests\Scouting\ScoutingMediaRequest;
use App\Http\Requests\Scouting\ScoutingNoteRequest;
use App\Http\Requests\Scouting\ScoutingProfileRequest;
use App\Http\Requests\Scouting\ScoutingReportRequest;
use App\Models\Organization;
use App\Models\Player;
use App\Models\ScoutingMedia;
use App\Models\ScoutingNote;
use App\Models\ScoutingProfile;
use App\Models\ScoutingRating;
use App\Models\ScoutingReport;
use App\Services\Scouting\ScoutingConversionService;
use App\Services\Scouting\ScoutingRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ScoutingController extends Controller
{
    public function __construct(
        private readonly ScoutingConversionService $conversion,
        private readonly ScoutingRatingService $ratingService
    ) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->assertCanView($request, $organization);

        $query = ScoutingProfile::query()
            ->where('organization_id', $organization->id)
            ->with([
                'convertedPlayer:id,display_name',
                'reports' => fn ($q) => $q->with('rating')->orderByDesc('report_date')->orderByDesc('id'),
            ]);

        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('display_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('current_team', 'like', $term)
                    ->orWhere('nationality', 'like', $term);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('competition')) {
            $competition = $request->string('competition')->toString();
            $query->where(function ($q) use ($competition) {
                $q->where('current_competition', $competition)
                    ->orWhereHas('reports', fn ($r) => $r->where('competition', $competition));
            });
        }

        if ($request->filled('recommendation')) {
            $recommendation = $request->string('recommendation')->toString();
            $query->whereHas('reports', fn ($q) => $q->where('overall_recommendation', $recommendation));
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        return response()->json(
            $query->orderByDesc('updated_at')->paginate($perPage)
        );
    }

    public function store(ScoutingProfileRequest $request, Organization $organization): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $data = $request->validated();

        if (! empty($data['existing_player_id'])) {
            $this->assertPlayerOrganization($organization, (int) $data['existing_player_id']);
        }

        $profile = ScoutingProfile::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'status' => $data['status'] ?? 'Watching',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Scouting profile created successfully.',
            'data' => $this->loadProfile($profile, $request),
        ], 201);
    }

    public function show(Request $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanView($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);

        return response()->json(['data' => $this->loadProfile($scoutingProfile, $request)]);
    }

    public function update(ScoutingProfileRequest $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);
        $data = $request->validated();

        if (! empty($data['existing_player_id'])) {
            $this->assertPlayerOrganization($organization, (int) $data['existing_player_id']);
        }

        $scoutingProfile->update($data);

        return response()->json([
            'message' => 'Scouting profile updated successfully.',
            'data' => $this->loadProfile($scoutingProfile->fresh(), $request),
        ]);
    }

    public function destroy(Request $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);

        foreach ($scoutingProfile->reports()->with('media')->get() as $report) {
            foreach ($report->media as $media) {
                $this->deleteStoredMedia($media);
            }
        }

        $scoutingProfile->delete();

        return response()->json(['message' => 'Scouting profile deleted successfully.']);
    }

    public function storeReport(ScoutingReportRequest $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);
        $data = $request->validated();

        $report = DB::transaction(function () use ($request, $scoutingProfile, $data) {
            $report = ScoutingReport::query()->create([
                'scouting_profile_id' => $scoutingProfile->id,
                'scout_id' => $request->user()->id,
                'competition' => $data['competition'] ?? null,
                'report_date' => $data['report_date'],
                'observed_role' => $data['observed_role'] ?? null,
                'strengths' => $data['strengths'] ?? null,
                'weaknesses' => $data['weaknesses'] ?? null,
                'potential' => $data['potential'] ?? null,
                'overall_recommendation' => $data['overall_recommendation'],
                'notes' => $data['notes'] ?? null,
            ]);
            $this->saveRating($report, $data);
            return $report;
        });

        return response()->json([
            'message' => 'Scouting report created successfully.',
            'data' => $this->loadReport($report, $request),
        ], 201);
    }

    public function showReport(Request $request, Organization $organization, ScoutingReport $scoutingReport): JsonResponse
    {
        $this->assertCanView($request, $organization);
        $this->assertReportOrganization($organization, $scoutingReport);
        return response()->json(['data' => $this->loadReport($scoutingReport, $request)]);
    }

    public function updateReport(ScoutingReportRequest $request, Organization $organization, ScoutingReport $scoutingReport): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertReportOrganization($organization, $scoutingReport);
        $data = $request->validated();

        DB::transaction(function () use ($scoutingReport, $data) {
            $scoutingReport->update([
                'competition' => $data['competition'] ?? null,
                'report_date' => $data['report_date'],
                'observed_role' => $data['observed_role'] ?? null,
                'strengths' => $data['strengths'] ?? null,
                'weaknesses' => $data['weaknesses'] ?? null,
                'potential' => $data['potential'] ?? null,
                'overall_recommendation' => $data['overall_recommendation'],
                'notes' => $data['notes'] ?? null,
            ]);
            $this->saveRating($scoutingReport, $data);
        });

        return response()->json([
            'message' => 'Scouting report updated successfully.',
            'data' => $this->loadReport($scoutingReport->fresh(), $request),
        ]);
    }

    public function destroyReport(Request $request, Organization $organization, ScoutingReport $scoutingReport): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertReportOrganization($organization, $scoutingReport);

        foreach ($scoutingReport->media as $media) {
            $this->deleteStoredMedia($media);
        }

        $scoutingReport->delete();
        return response()->json(['message' => 'Scouting report deleted successfully.']);
    }

    public function addMedia(ScoutingMediaRequest $request, Organization $organization, ScoutingReport $scoutingReport): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertReportOrganization($organization, $scoutingReport);
        $data = $request->validated();

        $payload = [
            'scouting_report_id' => $scoutingReport->id,
            'uploaded_by' => $request->user()->id,
            'media_type' => $data['media_type'],
            'caption' => $data['caption'] ?? null,
        ];

        if ($data['media_type'] === 'Video URL') {
            $payload['video_url'] = $data['video_url'];
        } else {
            $file = $request->file('file');
            $path = $file->store("scouting/{$organization->id}/reports/{$scoutingReport->id}", 'public');
            $payload += [
                'disk' => 'public',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ];
        }

        $media = ScoutingMedia::query()->create($payload);

        return response()->json([
            'message' => 'Scouting media added successfully.',
            'data' => $media->fresh(),
        ], 201);
    }

    public function destroyMedia(Request $request, Organization $organization, ScoutingMedia $scoutingMedia): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $scoutingMedia->loadMissing('report.profile');
        abort_unless((int) $scoutingMedia->report->profile->organization_id === (int) $organization->id, 404);

        $this->deleteStoredMedia($scoutingMedia);
        $scoutingMedia->delete();

        return response()->json(['message' => 'Scouting media deleted successfully.']);
    }

    public function addNote(ScoutingNoteRequest $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);
        $data = $request->validated();

        if (! empty($data['scouting_report_id'])) {
            $report = ScoutingReport::query()->findOrFail($data['scouting_report_id']);
            abort_unless((int) $report->scouting_profile_id === (int) $scoutingProfile->id, 422, 'The note report must belong to this scouting profile.');
        }

        $note = ScoutingNote::query()->create([
            'scouting_profile_id' => $scoutingProfile->id,
            'scouting_report_id' => $data['scouting_report_id'] ?? null,
            'author_id' => $request->user()->id,
            'note' => $data['note'],
            'is_private' => $data['is_private'] ?? false,
        ]);

        return response()->json([
            'message' => 'Scouting note added successfully.',
            'data' => $note->load('author:id,name'),
        ], 201);
    }

    public function destroyNote(Request $request, Organization $organization, ScoutingNote $scoutingNote): JsonResponse
    {
        $this->assertCanManage($request, $organization);
        $scoutingNote->loadMissing('profile');
        abort_unless((int) $scoutingNote->profile->organization_id === (int) $organization->id, 404);

        if (! $request->user()->can('access-administration')) {
            abort_unless((int) $scoutingNote->author_id === (int) $request->user()->id, 403, 'You may only delete your own scouting notes.');
        }

        $scoutingNote->delete();
        return response()->json(['message' => 'Scouting note deleted successfully.']);
    }

    public function compare(Request $request, Organization $organization): JsonResponse
    {
        $this->assertCanView($request, $organization);

        $data = $request->validate([
            'profile_ids' => ['required','array','min:2','max:4'],
            'profile_ids.*' => ['integer','distinct','exists:scouting_profiles,id'],
        ]);

        $profiles = ScoutingProfile::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $data['profile_ids'])
            ->with([
                'reports' => fn ($q) => $q->with('rating')->orderByDesc('report_date')->orderByDesc('id'),
            ])
            ->get();

        if ($profiles->count() !== count($data['profile_ids'])) {
            throw ValidationException::withMessages([
                'profile_ids' => 'Every compared profile must belong to the organization.',
            ]);
        }

        return response()->json([
            'data' => $profiles->map(function (ScoutingProfile $profile) {
                $latest = $profile->reports->first();
                return [
                    'profile' => $profile->only([
                        'id','display_name','role','nationality','current_team','current_competition','status',
                    ]),
                    'latest_report' => $latest ? [
                        'id' => $latest->id,
                        'report_date' => $latest->report_date,
                        'competition' => $latest->competition,
                        'potential' => $latest->potential,
                        'overall_recommendation' => $latest->overall_recommendation,
                        'rating' => $latest->rating,
                    ] : null,
                ];
            })->values(),
        ]);
    }

    public function convert(ConvertScoutingProfileRequest $request, Organization $organization, ScoutingProfile $scoutingProfile): JsonResponse
    {
        $this->assertCanConvert($request, $organization);
        $this->assertProfileOrganization($organization, $scoutingProfile);

        $player = $this->conversion->convert($scoutingProfile, $request->validated());

        return response()->json([
            'message' => 'Scouted player converted to organization player.',
            'data' => $player,
        ], 201);
    }

    private function saveRating(ScoutingReport $report, array $data): void
    {
        ScoutingRating::query()->updateOrCreate(
            ['scouting_report_id' => $report->id],
            [
                'technical_rating' => (int) $data['technical_rating'],
                'tactical_rating' => (int) $data['tactical_rating'],
                'physical_rating' => (int) $data['physical_rating'],
                'fielding_rating' => (int) $data['fielding_rating'],
                'mental_decision_rating' => (int) $data['mental_decision_rating'],
                'overall_rating' => $this->ratingService->overall($data),
            ]
        );
    }

    private function loadProfile(ScoutingProfile $profile, ?Request $request = null): ScoutingProfile
    {
        $profile->load([
            'existingPlayer:id,display_name',
            'convertedPlayer:id,display_name',
            'creator:id,name',
            'reports' => fn ($q) => $q->with(['scout:id,name','rating','media'])->orderByDesc('report_date')->orderByDesc('id'),
            'scoutingNotes.author:id,name',
        ]);

        if ($request && ! $request->user()->can('access-administration')) {
            $profile->setRelation('notes', $profile->notes->filter(fn ($note) => ! $note->is_private || (int) $note->author_id === (int) $request->user()->id)->values());
        }

        return $profile;
    }

    private function loadReport(ScoutingReport $report, ?Request $request = null): ScoutingReport
    {
        $report->load(['profile','scout:id,name','rating','media','scoutingNotes.author:id,name']);

        if ($request && ! $request->user()->can('access-administration')) {
            $report->setRelation('scoutingNotes', $report->scoutingNotes->filter(fn ($note) => ! $note->is_private || (int) $note->author_id === (int) $request->user()->id)->values());
        }

        return $report;
    }

    private function deleteStoredMedia(ScoutingMedia $media): void
    {
        if ($media->disk && $media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }
    }

    private function assertCanView(Request $request, Organization $organization): void
    {
        $this->assertOrganizationMembership($request, $organization);
        abort_unless(
            $request->user()->can('access-administration') ||
            $this->hasAnyRole($request->user(), ['Coach','Analyst','Selector','Team Manager']),
            403,
            'You are not authorized to access scouting.'
        );
    }

    private function assertCanManage(Request $request, Organization $organization): void
    {
        $this->assertOrganizationMembership($request, $organization);
        abort_unless(
            $request->user()->can('access-administration') ||
            $this->hasAnyRole($request->user(), ['Coach','Selector','Team Manager']),
            403,
            'You are not authorized to manage scouting.'
        );
    }

    private function assertCanConvert(Request $request, Organization $organization): void
    {
        $this->assertOrganizationMembership($request, $organization);
        abort_unless(
            $request->user()->can('access-administration') ||
            $this->hasAnyRole($request->user(), ['Selector','Team Manager']),
            403,
            'You are not authorized to convert scouted players.'
        );
    }

    private function assertOrganizationMembership(Request $request, Organization $organization): void
    {
        if ($request->user()->can('access-administration')) {
            return;
        }

        if (method_exists($request->user(), 'belongsToOrganization')) {
            abort_unless(
                $request->user()->belongsToOrganization((int) $organization->id),
                403,
                'You are not a member of this organization.'
            );
        }
    }

    private function hasAnyRole(object $user, array $roles): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return (bool) $user->hasAnyRole($roles);
        }

        if (method_exists($user, 'roles')) {
            return $user->roles()->whereIn('name', $roles)->exists();
        }

        return false;
    }

    private function assertProfileOrganization(Organization $organization, ScoutingProfile $profile): void
    {
        abort_unless((int) $profile->organization_id === (int) $organization->id, 404, 'Scouting profile not found in this organization.');
    }

    private function assertReportOrganization(Organization $organization, ScoutingReport $report): void
    {
        $report->loadMissing('profile');
        abort_unless((int) $report->profile->organization_id === (int) $organization->id, 404, 'Scouting report not found in this organization.');
    }

    private function assertPlayerOrganization(Organization $organization, int $playerId): void
    {
        abort_unless(
            Player::query()->whereKey($playerId)->where('organization_id', $organization->id)->exists(),
            422,
            'The existing player must belong to the organization.'
        );
    }
}
