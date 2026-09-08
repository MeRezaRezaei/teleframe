<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.loggedOut of auth.LoggedOut.
 *
 * bytes params carried as base64 strings: future_auth_token
 */
final class TlAuthLoggedOutData extends TlAuthLoggedOutAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $futureAuthToken,
    ) {
    }
}
