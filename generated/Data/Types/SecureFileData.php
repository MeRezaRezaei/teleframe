<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureFile of SecureFile.
 *
 * bytes params carried as base64 strings: file_hash, secret
 */
final class SecureFileData extends TlSecureFileAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public int $size,
    public int $dcId,
    public int $date,
    public string $fileHash,
    public string $secret,
    ) {
    }
}
