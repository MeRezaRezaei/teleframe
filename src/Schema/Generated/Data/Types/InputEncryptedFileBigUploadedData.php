<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputEncryptedFileBigUploaded of InputEncryptedFile.
 */
final class InputEncryptedFileBigUploadedData extends TlInputEncryptedFileAbstractData
{
    public function __construct(
    public int $id,
    public int $parts,
    public int $keyFingerprint,
    ) {
    }
}
