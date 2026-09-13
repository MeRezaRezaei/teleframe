<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.emailVerifiedLogin of account.EmailVerified.
 */
final class TlAccountEmailVerifiedLoginData extends TlAccountEmailVerifiedAbstractData
{
    public function __construct(
    public string $email,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAuthSentCodeAbstractData $sentCode,
    ) {
    }
}
