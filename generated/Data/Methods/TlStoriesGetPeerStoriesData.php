<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getPeerStories (crc32 2c4ada50), returns stories.PeerStories. */
final class TlStoriesGetPeerStoriesData extends Data
{
    public const METHOD = 'stories.getPeerStories';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
