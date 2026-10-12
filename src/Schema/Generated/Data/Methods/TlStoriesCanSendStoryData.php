<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.canSendStory (crc32 30eb63f0), returns stories.CanSendStoryCount. */
final class TlStoriesCanSendStoryData extends Data
{
    public const METHOD = 'stories.canSendStory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
