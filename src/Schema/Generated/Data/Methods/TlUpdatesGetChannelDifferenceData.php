<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method updates.getChannelDifference (crc32 03173d78), returns updates.ChannelDifference. */
final class TlUpdatesGetChannelDifferenceData extends Data
{
    public const METHOD = 'updates.getChannelDifference';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $force,
    public mixed $channel,
    public mixed $filter,
    public int $pts,
    public int $limit,
    ) {
    }
}
