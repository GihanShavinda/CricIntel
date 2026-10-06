<?php

use App\Models\CricketMatch;
use App\Models\User;

use Illuminate\Support\Facades\Broadcast;


/*
|--------------------------------------------------------------------------
| Private Match Channel
|--------------------------------------------------------------------------
|
| Used by the CricIntel real-time match centre.
|
| Only:
|
| - Administrators
| - Members of the organization that owns the match
|
| may subscribe to:
|
| private-match.{matchId}
|
*/

Broadcast::channel(
    'match.{matchId}',
    function (
        User $user,
        int $matchId
    ): bool {
        $match = CricketMatch::query()
            ->find($matchId);

        if (! $match) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Administrator
        |--------------------------------------------------------------------------
        */

        if (
            $user->can(
                'access-administration'
            )
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Organization Membership
        |--------------------------------------------------------------------------
        |
        | Only authorize the private match channel if the authenticated user
        | belongs to the organization that owns the match.
        |
        */

        if (
            ! method_exists(
                $user,
                'belongsToOrganization'
            )
        ) {
            return false;
        }

        return $user
            ->belongsToOrganization(
                (int) $match->organization_id
            );
    }
);


/*
|--------------------------------------------------------------------------
| P17 Private User Notification Channel
|--------------------------------------------------------------------------
|
| Laravel broadcast notifications use a private channel based on the
| notifiable model:
|
| private-App.Models.User.{id}
|
| The React notification center subscribes using:
|
| echo
|     .private(`App.Models.User.${user.id}`)
|     .notification(...)
|
| A user may subscribe ONLY to their own notification channel.
|
*/

Broadcast::channel(
    'App.Models.User.{id}',
    function (
        User $user,
        int $id
    ): bool {
        return (int) $user->id ===
            (int) $id;
    }
);
