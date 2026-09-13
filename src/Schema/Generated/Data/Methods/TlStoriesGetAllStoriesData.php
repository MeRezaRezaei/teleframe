<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getAllStories (crc32 eeb0d625), returns stories.AllStories. */
final class TlStoriesGetAllStoriesData extends Data
{
    public const METHOD = 'stories.getAllStories';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $next,
    public ?bool $hidden,
    public ?string $state,
    ) {
    }
}
