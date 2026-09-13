<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.reuploadCdnFile (crc32 9b2754a8), returns Vector<FileHash>. */
final class TlUploadReuploadCdnFileData extends Data
{
    public const METHOD = 'upload.reuploadCdnFile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $fileToken,
    public string $requestToken,
    ) {
    }
}
