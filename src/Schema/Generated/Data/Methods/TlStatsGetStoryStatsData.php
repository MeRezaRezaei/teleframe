<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getStoryStats (crc32 374fef40), returns stats.StoryStats. */
final class TlStatsGetStoryStatsData extends Data
{
    public const METHOD = 'stats.getStoryStats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public mixed $peer,
    public int $id,
    ) {
    }
}
