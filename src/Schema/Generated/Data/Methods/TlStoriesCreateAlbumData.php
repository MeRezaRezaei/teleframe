<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.createAlbum (crc32 a36396e5), returns StoryAlbum. */
final class TlStoriesCreateAlbumData extends Data
{
    public const METHOD = 'stories.createAlbum';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $title,
    public array $stories,
    ) {
    }
}
