<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for encryptedFile of EncryptedFile.
 */
final class EncryptedFileData extends TlEncryptedFileAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public int $size,
    public int $dcId,
    public int $keyFingerprint,
    ) {
    }
}
