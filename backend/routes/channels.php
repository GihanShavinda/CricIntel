<?php

use App\Models\CricketMatch;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'match.{matchId}',
    function ($user, int $matchId) {
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
