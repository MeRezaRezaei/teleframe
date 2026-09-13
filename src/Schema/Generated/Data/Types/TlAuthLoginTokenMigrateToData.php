<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.loginTokenMigrateTo of auth.LoginToken.
 *
 * bytes params carried as base64 strings: token
 */
final class TlAuthLoginTokenMigrateToData extends TlAuthLoginTokenAbstractData
{
    public function __construct(
    public int $dcId,
    public string $token,
    ) {
    }
}
