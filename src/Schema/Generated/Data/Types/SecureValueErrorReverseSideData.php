<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureValueErrorReverseSide of SecureValueError.
 *
 * bytes params carried as base64 strings: file_hash
 */
final class SecureValueErrorReverseSideData extends TlSecureValueErrorAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    public string $fileHash,
    public string $text,
    ) {
    }
}
