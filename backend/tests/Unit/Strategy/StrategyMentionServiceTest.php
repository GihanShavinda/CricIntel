<?php

namespace Tests\Unit\Strategy;

use App\Models\User;
use App\Services\Strategy\StrategyMentionService;
use PHPUnit\Framework\TestCase;

class StrategyMentionServiceTest extends TestCase
{
    public function test_user_name_becomes_stable_mention_token(): void
    {
        $user = new User([
            'name' => 'Nimal Perera',
            'email' => 'nimal@example.com',
        ]);

        $service = new StrategyMentionService();

        $this->assertSame(
            'nimal.perera',
            $service->tokenFor($user)
        );
    }

    public function test_non_alphanumeric_name_spacing_is_normalized(): void
    {
        $user = new User([
            'name' => 'A. Silva - Analyst',
            'email' => 'analyst@example.com',
        ]);

        $service = new StrategyMentionService();

        $this->assertSame(
            'a.silva.analyst',
            $service->tokenFor($user)
        );
    }
}
