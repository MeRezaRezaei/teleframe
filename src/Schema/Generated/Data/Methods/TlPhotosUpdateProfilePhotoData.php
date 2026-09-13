<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method photos.updateProfilePhoto (crc32 09e82039), returns photos.Photo. */
final class TlPhotosUpdateProfilePhotoData extends Data
{
    public const METHOD = 'photos.updateProfilePhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $fallback,
    public mixed $bot,
    public mixed $id,
    ) {
    }
}
