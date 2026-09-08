<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.readStories (crc32 a556dac8), returns Vector<int>. */
final class TlStoriesReadStoriesData extends Data
{
    public const METHOD = 'stories.readStories';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $maxId,
    ) {
    }
}
