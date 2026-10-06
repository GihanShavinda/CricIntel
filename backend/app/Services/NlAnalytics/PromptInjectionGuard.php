<?php

namespace App\Services\NlAnalytics;

use Illuminate\Validation\ValidationException;

class PromptInjectionGuard
{
    private const BLOCKED_PATTERNS = [
        '/\bignore\s+(all|any|the|previous|prior)\s+(instructions|rules|prompt)/i',
        '/\bsystem\s+prompt\b/i',
        '/\bdeveloper\s+message\b/i',
        '/\bshow\s+me\s+(the\s+)?prompt\b/i',
        '/\bdrop\s+table\b/i',
        '/\bdelete\s+from\b/i',
        '/\btruncate\s+table\b/i',
        '/\binsert\s+into\b/i',
        '/\bupdate\s+\w+\s+set\b/i',
        '/\bselect\s+.+\s+from\b/i',
        '/\bunion\s+select\b/i',
        '/--/',
        '/\/\*/',
        '/\*\//',
        '/;\s*(drop|delete|truncate|insert|update|select)\b/i',
        '/\bexecute\s+(sql|query|command)\b/i',
        '/\braw\s+sql\b/i',
    ];

    public function assertSafe(string $query): void
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $query)) {
                throw ValidationException::withMessages([
                    'query' =>
                        'This request contains unsupported or unsafe instructions. ' .
                        'Ask only for cricket analytics supported by CricIntel.',
                ]);
            }
        }
    }
}
