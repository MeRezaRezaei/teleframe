<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for upload.webFile of upload.WebFile.
 *
 * bytes params carried as base64 strings: bytes
 */
final class TlUploadWebFileData extends TlUploadWebFileAbstractData
{
    public function __construct(
    public int $size,
    public string $mimeType,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileTypeAbstractData $fileType,
    public int $mtime,
    public string $bytes,
    ) {
    }
}
