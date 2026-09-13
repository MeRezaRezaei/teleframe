<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for upload.file of upload.File.
 *
 * bytes params carried as base64 strings: bytes
 */
final class TlUploadFileData extends TlUploadFileAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileTypeAbstractData $type,
    public int $mtime,
    public string $bytes,
    ) {
    }
}
