<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.saveFilePart (crc32 b304a621), returns Bool. */
final class TlUploadSaveFilePartData extends Data
{
    public const METHOD = 'upload.saveFilePart';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $fileId,
    public int $filePart,
    public string $bytes,
    ) {
    }
}
