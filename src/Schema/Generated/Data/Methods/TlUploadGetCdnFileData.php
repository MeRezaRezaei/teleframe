<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.getCdnFile (crc32 395f69da), returns upload.CdnFile. */
final class TlUploadGetCdnFileData extends Data
{
    public const METHOD = 'upload.getCdnFile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $fileToken,
    public int $offset,
    public int $limit,
    ) {
    }
}
