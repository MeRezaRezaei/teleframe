<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureValueErrorFile of SecureValueError.
 *
 * bytes params carried as base64 strings: file_hash
 */
final class SecureValueErrorFileData extends TlSecureValueErrorAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    public string $fileHash,
    public string $text,
    ) {
    }
}
