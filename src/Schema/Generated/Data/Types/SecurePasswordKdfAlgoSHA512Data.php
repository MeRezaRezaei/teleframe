<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for securePasswordKdfAlgoSHA512 of SecurePasswordKdfAlgo.
 *
 * bytes params carried as base64 strings: salt
 */
final class SecurePasswordKdfAlgoSHA512Data extends TlSecurePasswordKdfAlgoAbstractData
{
    public function __construct(
    public string $salt,
    ) {
    }
}
