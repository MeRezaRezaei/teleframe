<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.deleteAlbum (crc32 8d3456d0), returns Bool. */
final class TlStoriesDeleteAlbumData extends Data
{
    public const METHOD = 'stories.deleteAlbum';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $albumId,
    ) {
    }
}
