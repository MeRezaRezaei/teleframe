<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getPinnedStories (crc32 5821a5dc), returns stories.Stories. */
final class TlStoriesGetPinnedStoriesData extends Data
{
    public const METHOD = 'stories.getPinnedStories';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $offsetId,
    public int $limit,
    ) {
    }
}
