<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.readMessageContents (crc32 36a73f77), returns messages.AffectedMessages. */
final class TlMessagesReadMessageContentsData extends Data
{
    public const METHOD = 'messages.readMessageContents';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
