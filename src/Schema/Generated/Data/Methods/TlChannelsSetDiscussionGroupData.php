<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.setDiscussionGroup (crc32 40582bb2), returns Bool. */
final class TlChannelsSetDiscussionGroupData extends Data
{
    public const METHOD = 'channels.setDiscussionGroup';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $broadcast,
    public mixed $group,
    ) {
    }
}
