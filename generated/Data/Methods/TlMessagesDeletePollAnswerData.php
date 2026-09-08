<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deletePollAnswer (crc32 ac8505a5), returns Updates. */
final class TlMessagesDeletePollAnswerData extends Data
{
    public const METHOD = 'messages.deletePollAnswer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public string $option,
    ) {
    }
}
