<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.getWebFile (crc32 24e6818d), returns upload.WebFile. */
final class TlUploadGetWebFileData extends Data
{
    public const METHOD = 'upload.getWebFile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $location,
    public int $offset,
    public int $limit,
    ) {
    }
}
