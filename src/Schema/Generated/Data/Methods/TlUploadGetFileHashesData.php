<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.getFileHashes (crc32 9156982a), returns Vector<FileHash>. */
final class TlUploadGetFileHashesData extends Data
{
    public const METHOD = 'upload.getFileHashes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $location,
    public int $offset,
    ) {
    }
}
