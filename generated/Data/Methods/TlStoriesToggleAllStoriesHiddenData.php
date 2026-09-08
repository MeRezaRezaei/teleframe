<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.toggleAllStoriesHidden (crc32 7c2557c4), returns Bool. */
final class TlStoriesToggleAllStoriesHiddenData extends Data
{
    public const METHOD = 'stories.toggleAllStoriesHidden';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $hidden,
    ) {
    }
}
