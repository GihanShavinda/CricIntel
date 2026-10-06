<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request
            ->user()
            ->notifications()
            ->latest();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        if ($request->filled('organization_id')) {
            $organizationId =
                $request->integer('organization_id');

            $query->whereRaw(
                "(data::jsonb ->> 'organization_id')::bigint = ?",
                [$organizationId]
            );
        }

        if ($request->filled('type')) {
            $query->whereRaw(
                "data::jsonb ->> 'type' = ?",
                [
                    $request->string('type')->toString(),
                ]
            );
        }

        $perPage = min(
            max(
                $request->integer('per_page', 20),
                1
            ),
            100
        );

        return response()->json([
            'data' => $query
                ->paginate($perPage)
                ->through(
                    fn (DatabaseNotification $notification) =>
                        $this->serialize($notification)
                ),
            'meta' => [
                'unread_count' =>
                    $request
                        ->user()
                        ->unreadNotifications()
                        ->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' =>
                    $request
                        ->user()
                        ->unreadNotifications()
                        ->count(),
            ],
        ]);
    }

    public function markRead(
        Request $request,
        string $notification
    ): JsonResponse {
        $row = $this->findOwned(
            $request,
            $notification
        );

        $row->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => $this->serialize(
                $row->fresh()
            ),
        ]);
    }

    public function markUnread(
        Request $request,
        string $notification
    ): JsonResponse {
        $row = $this->findOwned(
            $request,
            $notification
        );

        $row->update([
            'read_at' => null,
        ]);

        return response()->json([
            'message' => 'Notification marked as unread.',
            'data' => $this->serialize(
                $row->fresh()
            ),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request
            ->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' =>
                'All notifications marked as read.',
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        string $notification
    ): JsonResponse {
        $row = $this->findOwned(
            $request,
            $notification
        );

        $row->delete();

        return response()->json([
            'message' => 'Notification deleted.',
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        return response()->json([
            'data' =>
                $this
                    ->preferences
                    ->defaultsFor(
                        $request->user()
                    ),
        ]);
    }

    public function updatePreferences(
        UpdateNotificationPreferencesRequest $request
    ): JsonResponse {
        foreach (
            $request->validated('preferences') as
            $preference
        ) {
            NotificationPreference::query()
                ->updateOrCreate(
                    [
                        'user_id' =>
                            $request->user()->id,
                        'event_type' =>
                            $preference['event_type'],
                    ],
                    [
                        'database_enabled' =>
                            $preference['database_enabled'],
                        'email_enabled' =>
                            $preference['email_enabled'],
                        'realtime_enabled' =>
                            $preference['realtime_enabled'],
                    ]
                );
        }

        return response()->json([
            'message' =>
                'Notification preferences updated.',
            'data' =>
                $this
                    ->preferences
                    ->defaultsFor(
                        $request->user()
                    ),
        ]);
    }

    private function findOwned(
        Request $request,
        string $notification
    ): DatabaseNotification {
        return $request
            ->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();
    }

    private function serialize(
        DatabaseNotification $notification
    ): array {
        return [
            'id' => $notification->id,
            'type' =>
                $notification->data['type'] ??
                class_basename(
                    $notification->type
                ),
            'title' =>
                $notification->data['title'] ??
                'CricIntel notification',
            'message' =>
                $notification->data['message'] ??
                '',
            'url' =>
                $notification->data['url'] ??
                null,
            'organization_id' =>
                $notification->data['organization_id'] ??
                null,
            'actor_id' =>
                $notification->data['actor_id'] ??
                null,
            'data' =>
                $notification->data['data'] ??
                [],
            'read_at' =>
                optional($notification->read_at)
                    ->toIso8601String(),
            'created_at' =>
                optional($notification->created_at)
                    ->toIso8601String(),
        ];
    }
}
