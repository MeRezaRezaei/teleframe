<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getStoriesArchive (crc32 b4352016), returns stories.Stories. */
final class TlStoriesGetStoriesArchiveData extends Data
{
    public const METHOD = 'stories.getStoriesArchive';

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
