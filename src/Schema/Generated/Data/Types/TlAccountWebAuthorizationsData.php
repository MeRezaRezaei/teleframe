<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.webAuthorizations of account.WebAuthorizations.
 */
final class TlAccountWebAuthorizationsData extends TlAccountWebAuthorizationsAbstractData
{
    public function __construct(
    public array $authorizations,
    public array $users,
    ) {
    }
}
