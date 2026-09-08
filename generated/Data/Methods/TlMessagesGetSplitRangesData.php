<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSplitRanges (crc32 1cff7e08), returns Vector<MessageRange>. */
final class TlMessagesGetSplitRangesData extends Data
{
    public const METHOD = 'messages.getSplitRanges';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
