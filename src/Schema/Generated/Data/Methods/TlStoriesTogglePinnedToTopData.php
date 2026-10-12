<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.togglePinnedToTop (crc32 0b297e9b), returns Bool. */
final class TlStoriesTogglePinnedToTopData extends Data
{
    public const METHOD = 'stories.togglePinnedToTop';

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
