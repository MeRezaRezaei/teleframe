<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method upload.getFile (crc32 be5335be), returns upload.File. */
final class TlUploadGetFileData extends Data
{
    public const METHOD = 'upload.getFile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $precise,
    public ?bool $cdnSupported,
    public mixed $location,
    public int $offset,
    public int $limit,
    ) {
    }
}
