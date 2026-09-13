<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method photos.uploadProfilePhoto (crc32 0388a3b5), returns photos.Photo. */
final class TlPhotosUploadProfilePhotoData extends Data
{
    public const METHOD = 'photos.uploadProfilePhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $fallback,
    public mixed $bot,
    public mixed $file,
    public mixed $video,
    public ?float $videoStartTs,
    public mixed $videoEmojiMarkup,
    ) {
    }
}
