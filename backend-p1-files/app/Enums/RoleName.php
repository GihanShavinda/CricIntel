<?php

namespace App\Enums;

enum RoleName: string
{
    case Administrator = 'Administrator';
    case Coach = 'Coach';
    case Analyst = 'Analyst';
    case Selector = 'Selector';
    case TeamManager = 'Team Manager';
    case Player = 'Player';

    public static function values(): array
    {
        return array_map(
            fn (self $role) => $role->value,
            self::cases()
        );
    }
}
