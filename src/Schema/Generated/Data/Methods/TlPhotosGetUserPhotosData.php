<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method photos.getUserPhotos (crc32 91cd32a8), returns photos.Photos. */
final class TlPhotosGetUserPhotosData extends Data
{
    public const METHOD = 'photos.getUserPhotos';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public int $offset,
    public int $maxId,
    public int $limit,
    ) {
    }
}
