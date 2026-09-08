<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputSecureFileUploaded of InputSecureFile.
 *
 * bytes params carried as base64 strings: file_hash, secret
 */
final class InputSecureFileUploadedData extends TlInputSecureFileAbstractData
{
    public function __construct(
    public int $id,
    public int $parts,
    public string $md5Checksum,
    public string $fileHash,
    public string $secret,
    ) {
    }
}
