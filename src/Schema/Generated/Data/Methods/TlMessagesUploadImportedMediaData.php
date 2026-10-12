<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.uploadImportedMedia (crc32 2a862092), returns MessageMedia. */
final class TlMessagesUploadImportedMediaData extends Data
{
    public const METHOD = 'messages.uploadImportedMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $importId,
    public string $fileName,
    public mixed $media,
    ) {
    }
}
