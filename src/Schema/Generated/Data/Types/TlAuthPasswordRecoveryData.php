<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.passwordRecovery of auth.PasswordRecovery.
 */
final class TlAuthPasswordRecoveryData extends TlAuthPasswordRecoveryAbstractData
{
    public function __construct(
    public string $emailPattern,
    ) {
    }
}
