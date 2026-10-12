<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.updateAlbum (crc32 5e5259b6), returns StoryAlbum. */
final class TlStoriesUpdateAlbumData extends Data
{
    public const METHOD = 'stories.updateAlbum';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $albumId,
    public ?string $title,
    public ?array $deleteStories,
    public ?array $addStories,
    public ?array $order,
    ) {
    }
}
