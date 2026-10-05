<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Strategy\StrategyAssignmentRequest;
use App\Http\Requests\Strategy\StrategyAttachmentRequest;
use App\Http\Requests\Strategy\StrategyCommentRequest;
use App\Http\Requests\Strategy\TacticalNoteRequest;
use App\Models\Mention;
use App\Models\Organization;
use App\Models\StrategyAssignment;
use App\Models\StrategyAttachment;
use App\Models\StrategyComment;
use App\Models\StrategyPlan;
use App\Models\TacticalNote;
use App\Services\Strategy\StrategyAccessService;
use App\Services\Strategy\StrategyAttachmentService;
use App\Services\Strategy\StrategyMentionService;
use App\Services\Strategy\StrategyVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StrategyCollaborationController extends Controller
{
    public function __construct(
        private readonly StrategyAccessService $access,
        private readonly StrategyMentionService $mentions,
        private readonly StrategyAttachmentService $attachments,
        private readonly StrategyVersionService $versions
    ) {}

    public function storeNote(
        TacticalNoteRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertUnlocked($strategyPlan);

        $data = $request->validated();

        if (! empty($data['strategy_section_id'])) {
            abort_unless(
                $strategyPlan->sections()
                    ->whereKey($data['strategy_section_id'])
                    ->exists(),
                422,
                'The section must belong to this strategy plan.'
            );
        }

        $note = DB::transaction(function () use ($request, $organization, $strategyPlan, $data) {
            $note = TacticalNote::query()->create([
                'strategy_plan_id' => $strategyPlan->id,
                'strategy_section_id' => $data['strategy_section_id'] ?? null,
                'author_id' => $request->user()->id,
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
            ]);

            $this->mentions->sync(
                $strategyPlan,
                $organization,
                'note',
                $note->id,
                $this->mentions->resolveUserIds(
                    $organization,
                    $data['body'],
                    $data['mention_user_ids'] ?? []
                ),
                $request->user()
            );

            $this->versions->record(
                $strategyPlan,
                $request->user(),
                'note.created',
                'tactical_note',
                $note->id,
                'Tactical note created.',
                null,
                $note
            );

            return $note;
        });

        return response()->json([
            'message' => 'Tactical note created.',
            'data' => $note->load([
                'section:id,title,section_key',
                'author:id,name,email',
                'comments.author:id,name,email',
            ]),
        ], 201);
    }

    public function updateNote(
        TacticalNoteRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        TacticalNote $tacticalNote
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertNotePlan($strategyPlan, $tacticalNote);
        $this->assertUnlocked($strategyPlan);
        $this->assertAuthorOrCoach($request, $organization, $tacticalNote->author_id);

        $data = $request->validated();
        $before = $tacticalNote->toArray();

        $tacticalNote->update([
            'strategy_section_id' => $data['strategy_section_id'] ?? null,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);

        $this->mentions->sync(
            $strategyPlan,
            $organization,
            'note',
            $tacticalNote->id,
            $this->mentions->resolveUserIds(
                $organization,
                $data['body'],
                $data['mention_user_ids'] ?? []
            ),
            $request->user()
        );

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'note.updated',
            'tactical_note',
            $tacticalNote->id,
            'Tactical note updated.',
            $before,
            $tacticalNote->fresh()
        );

        return response()->json([
            'data' => $tacticalNote->fresh()->load([
                'section:id,title,section_key',
                'author:id,name,email',
                'comments.author:id,name,email',
            ]),
        ]);
    }

    public function resolveNote(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        TacticalNote $tacticalNote
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertNotePlan($strategyPlan, $tacticalNote);
        $this->assertUnlocked($strategyPlan);

        $before = $tacticalNote->toArray();
        $resolved = $tacticalNote->status !== 'Resolved';

        $tacticalNote->update([
            'status' => $resolved ? 'Resolved' : 'Open',
            'resolved_by' => $resolved ? $request->user()->id : null,
            'resolved_at' => $resolved ? now() : null,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            $resolved ? 'discussion.resolved' : 'discussion.reopened',
            'tactical_note',
            $tacticalNote->id,
            $resolved ? 'Discussion resolved.' : 'Discussion reopened.',
            $before,
            $tacticalNote->fresh()
        );

        return response()->json([
            'data' => $tacticalNote->fresh()->load([
                'author:id,name,email',
                'resolver:id,name,email',
                'comments.author:id,name,email',
            ]),
        ]);
    }

    public function destroyNote(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        TacticalNote $tacticalNote
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertNotePlan($strategyPlan, $tacticalNote);
        $this->assertUnlocked($strategyPlan);
        $this->assertAuthorOrCoach($request, $organization, $tacticalNote->author_id);

        $before = $tacticalNote->toArray();
        $id = $tacticalNote->id;

        Mention::query()
            ->where('strategy_plan_id', $strategyPlan->id)
            ->where('source_type', 'note')
            ->where('source_id', $id)
            ->delete();

        $tacticalNote->delete();

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'note.deleted',
            'tactical_note',
            $id,
            'Tactical note deleted.',
            $before,
            null
        );

        return response()->json([
            'message' => 'Tactical note deleted.',
        ]);
    }

    public function storeComment(
        StrategyCommentRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        TacticalNote $tacticalNote
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertNotePlan($strategyPlan, $tacticalNote);
        $this->assertUnlocked($strategyPlan);

        $data = $request->validated();

        $comment = StrategyComment::query()->create([
            'tactical_note_id' => $tacticalNote->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $this->mentions->sync(
            $strategyPlan,
            $organization,
            'comment',
            $comment->id,
            $this->mentions->resolveUserIds(
                $organization,
                $data['body'],
                $data['mention_user_ids'] ?? []
            ),
            $request->user()
        );

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'comment.created',
            'comment',
            $comment->id,
            'Comment added to tactical discussion.',
            null,
            $comment
        );

        return response()->json([
            'data' => $comment->load('author:id,name,email'),
        ], 201);
    }

    public function destroyComment(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        StrategyComment $comment
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        $comment->loadMissing('note');

        abort_unless(
            (int) $comment->note->strategy_plan_id === (int) $strategyPlan->id,
            404
        );

        $this->assertAuthorOrCoach($request, $organization, $comment->author_id);

        $before = $comment->toArray();
        $id = $comment->id;

        Mention::query()
            ->where('strategy_plan_id', $strategyPlan->id)
            ->where('source_type', 'comment')
            ->where('source_id', $id)
            ->delete();

        $comment->delete();

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'comment.deleted',
            'comment',
            $id,
            'Comment deleted.',
            $before,
            null
        );

        return response()->json([
            'message' => 'Comment deleted.',
        ]);
    }

    public function storeAttachment(
        StrategyAttachmentRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertUnlocked($strategyPlan);

        $attachment = $this->attachments->create(
            $strategyPlan,
            $request->user()->id,
            $request->validated(),
            $request->file('file')
        );

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'attachment.created',
            'attachment',
            $attachment->id,
            "Attachment added: {$attachment->attachment_type}.",
            null,
            $attachment
        );

        return response()->json([
            'data' => $attachment->fresh()->load('uploader:id,name,email'),
        ], 201);
    }

    public function destroyAttachment(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        StrategyAttachment $attachment
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_unless(
            (int) $attachment->strategy_plan_id === (int) $strategyPlan->id,
            404
        );

        $this->assertAuthorOrCoach($request, $organization, $attachment->uploaded_by);

        $before = $attachment->toArray();
        $id = $attachment->id;

        $this->attachments->delete($attachment);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'attachment.deleted',
            'attachment',
            $id,
            'Attachment deleted.',
            $before,
            null
        );

        return response()->json([
            'message' => 'Attachment deleted.',
        ]);
    }

    public function storeAssignment(
        StrategyAssignmentRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);
        $this->assertUnlocked($strategyPlan);

        $data = $request->validated();

        $this->assertCollaborator($organization, (int) $data['assigned_to']);

        if (! empty($data['strategy_section_id'])) {
            abort_unless(
                $strategyPlan->sections()->whereKey($data['strategy_section_id'])->exists(),
                422
            );
        }

        $assignment = StrategyAssignment::query()->create([
            'strategy_plan_id' => $strategyPlan->id,
            'strategy_section_id' => $data['strategy_section_id'] ?? null,
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'Todo',
            'due_at' => $data['due_at'] ?? null,
            'completed_at' => ($data['status'] ?? null) === 'Done' ? now() : null,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'assignment.created',
            'strategy_assignment',
            $assignment->id,
            'Strategy assignment created.',
            null,
            $assignment
        );

        return response()->json([
            'data' => $assignment->load([
                'section:id,title,section_key',
                'assignee:id,name,email',
                'assigner:id,name,email',
            ]),
        ], 201);
    }

    public function updateAssignment(
        StrategyAssignmentRequest $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        StrategyAssignment $strategyAssignment
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_unless(
            (int) $strategyAssignment->strategy_plan_id === (int) $strategyPlan->id,
            404
        );

        $isManager = $request->user()->can('access-administration') ||
            $request->user()->hasRole('Coach');

        abort_unless(
            $isManager ||
            (int) $strategyAssignment->assigned_to === (int) $request->user()->id,
            403,
            'Only the assignee or a coach can update this assignment.'
        );

        $data = $request->validated();
        $this->assertCollaborator($organization, (int) $data['assigned_to']);

        $before = $strategyAssignment->toArray();

        $strategyAssignment->update([
            'strategy_section_id' => $data['strategy_section_id'] ?? null,
            'assigned_to' => $data['assigned_to'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? $strategyAssignment->status,
            'due_at' => $data['due_at'] ?? null,
            'completed_at' => ($data['status'] ?? null) === 'Done'
                ? ($strategyAssignment->completed_at ?? now())
                : null,
        ]);

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'assignment.updated',
            'strategy_assignment',
            $strategyAssignment->id,
            'Strategy assignment updated.',
            $before,
            $strategyAssignment->fresh()
        );

        return response()->json([
            'data' => $strategyAssignment->fresh()->load([
                'section:id,title,section_key',
                'assignee:id,name,email',
                'assigner:id,name,email',
            ]),
        ]);
    }

    public function destroyAssignment(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        StrategyAssignment $strategyAssignment
    ): JsonResponse {
        $this->access->assertManage($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_unless(
            (int) $strategyAssignment->strategy_plan_id === (int) $strategyPlan->id,
            404
        );

        $before = $strategyAssignment->toArray();
        $id = $strategyAssignment->id;
        $strategyAssignment->delete();

        $this->versions->record(
            $strategyPlan,
            $request->user(),
            'assignment.deleted',
            'strategy_assignment',
            $id,
            'Strategy assignment deleted.',
            $before,
            null
        );

        return response()->json([
            'message' => 'Assignment deleted.',
        ]);
    }

    public function markMentionRead(
        Request $request,
        Organization $organization,
        StrategyPlan $strategyPlan,
        Mention $mention
    ): JsonResponse {
        $this->access->assertCollaborate($request, $organization);
        $this->access->assertPlanOrganization($organization, $strategyPlan);

        abort_unless(
            (int) $mention->strategy_plan_id === (int) $strategyPlan->id &&
            (int) $mention->mentioned_user_id === (int) $request->user()->id,
            403
        );

        $mention->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'data' => $mention->fresh(),
        ]);
    }

    private function assertNotePlan(
        StrategyPlan $plan,
        TacticalNote $note
    ): void {
        abort_unless(
            (int) $note->strategy_plan_id === (int) $plan->id,
            404
        );
    }

    private function assertUnlocked(StrategyPlan $plan): void
    {
        abort_if(
            $plan->locked_at,
            422,
            'This strategy plan is locked.'
        );
    }

    private function assertAuthorOrCoach(
        Request $request,
        Organization $organization,
        ?int $ownerId
    ): void {
        if ((int) $ownerId === (int) $request->user()->id) {
            return;
        }

        $this->access->assertManage($request, $organization);
    }

    private function assertCollaborator(
        Organization $organization,
        int $userId
    ): void {
        $valid = DB::table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();

        abort_unless(
            $valid,
            422,
            'The assignee must be an active organization member.'
        );
    }
}
