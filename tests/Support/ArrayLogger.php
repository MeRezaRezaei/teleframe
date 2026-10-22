<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 test double: every record is appended to a public array so tests
 * can assert level, message and structured context.
 */
final class ArrayLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}