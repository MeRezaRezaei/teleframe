<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method photos.deletePhotos (crc32 87cf7f2f), returns Vector<long>. */
final class TlPhotosDeletePhotosData extends Data
{
    public const METHOD = 'photos.deletePhotos';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
