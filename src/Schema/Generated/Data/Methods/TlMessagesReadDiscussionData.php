<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.readDiscussion (crc32 f731a9f4), returns Bool. */
final class TlMessagesReadDiscussionData extends Data
{
    public const METHOD = 'messages.readDiscussion';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public int $readMaxId,
    ) {
    }
}
