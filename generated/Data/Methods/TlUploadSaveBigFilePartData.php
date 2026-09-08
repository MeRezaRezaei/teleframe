<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.saveBigFilePart (crc32 de7b673d), returns Bool. */
final class TlUploadSaveBigFilePartData extends Data
{
    public const METHOD = 'upload.saveBigFilePart';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $fileId,
    public int $filePart,
    public int $fileTotalParts,
    public string $bytes,
    ) {
    }
}
