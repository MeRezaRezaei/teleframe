<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getStoriesByID (crc32 5774ca74), returns stories.Stories. */
final class TlStoriesGetStoriesByIDData extends Data
{
    public const METHOD = 'stories.getStoriesByID';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $id,
    ) {
    }
}
