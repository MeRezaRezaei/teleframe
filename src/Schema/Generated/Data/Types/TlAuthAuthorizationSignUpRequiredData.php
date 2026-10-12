<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.authorizationSignUpRequired of auth.Authorization.
 */
final class TlAuthAuthorizationSignUpRequiredData extends TlAuthAuthorizationAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlHelpTermsOfServiceAbstractData $termsOfService,
    ) {
    }
}
