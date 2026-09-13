<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleTodoCompleted (crc32 d3e03124), returns Updates. */
final class TlMessagesToggleTodoCompletedData extends Data
{
    public const METHOD = 'messages.toggleTodoCompleted';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public array $completed,
    public array $incompleted,
    ) {
    }
}
