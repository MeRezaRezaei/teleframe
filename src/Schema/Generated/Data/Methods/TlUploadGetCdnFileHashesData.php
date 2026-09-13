<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.getCdnFileHashes (crc32 91dc3f31), returns Vector<FileHash>. */
final class TlUploadGetCdnFileHashesData extends Data
{
    public const METHOD = 'upload.getCdnFileHashes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $fileToken,
    public int $offset,
    ) {
    }
}
