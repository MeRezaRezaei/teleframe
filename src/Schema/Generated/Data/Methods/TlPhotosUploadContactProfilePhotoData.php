<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method photos.uploadContactProfilePhoto (crc32 e14c4a71), returns photos.Photo. */
final class TlPhotosUploadContactProfilePhotoData extends Data
{
    public const METHOD = 'photos.uploadContactProfilePhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $suggest,
    public ?bool $save,
    public mixed $userId,
    public mixed $file,
    public mixed $video,
    public ?float $videoStartTs,
    public mixed $videoEmojiMarkup,
    ) {
    }
}
