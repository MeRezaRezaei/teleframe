<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteTopicHistory (crc32 d2816f10), returns messages.AffectedHistory. */
final class TlMessagesDeleteTopicHistoryData extends Data
{
    public const METHOD = 'messages.deleteTopicHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $topMsgId,
    ) {
    }
}
