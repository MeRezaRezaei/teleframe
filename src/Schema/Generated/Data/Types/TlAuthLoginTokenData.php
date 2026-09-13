<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.loginToken of auth.LoginToken.
 *
 * bytes params carried as base64 strings: token
 */
final class TlAuthLoginTokenData extends TlAuthLoginTokenAbstractData
{
    public function __construct(
    public int $expires,
    public string $token,
    ) {
    }
}
