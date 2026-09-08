<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for securePasswordKdfAlgoPBKDF2HMACSHA512iter100000 of SecurePasswordKdfAlgo.
 *
 * bytes params carried as base64 strings: salt
 */
final class SecurePasswordKdfAlgoPBKDF2HMACSHA512iter100000Data extends TlSecurePasswordKdfAlgoAbstractData
{
    public function __construct(
    public string $salt,
    ) {
    }
}
