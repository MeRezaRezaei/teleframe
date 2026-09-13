<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow of PasswordKdfAlgo.
 *
 * bytes params carried as base64 strings: salt1, salt2, p
 */
final class PasswordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPowData extends TlPasswordKdfAlgoAbstractData
{
    public function __construct(
    public string $salt1,
    public string $salt2,
    public int $g,
    public string $p,
    ) {
    }
}
