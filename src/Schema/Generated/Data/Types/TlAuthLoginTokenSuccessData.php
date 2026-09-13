<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.loginTokenSuccess of auth.LoginToken.
 */
final class TlAuthLoginTokenSuccessData extends TlAuthLoginTokenAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAuthAuthorizationAbstractData $authorization,
    ) {
    }
}
