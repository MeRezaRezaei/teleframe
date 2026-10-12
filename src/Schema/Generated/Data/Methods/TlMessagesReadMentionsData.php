<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.readMentions (crc32 36e5bf4d), returns messages.AffectedHistory. */
final class TlMessagesReadMentionsData extends Data
{
    public const METHOD = 'messages.readMentions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $topMsgId,
    ) {
    }
}
