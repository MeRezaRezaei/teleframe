<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.addPollAnswer (crc32 19bc4b6d), returns Updates. */
final class TlMessagesAddPollAnswerData extends Data
{
    public const METHOD = 'messages.addPollAnswer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public mixed $answer,
    ) {
    }
}
