<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.authorizationForm of account.AuthorizationForm.
 */
final class TlAccountAuthorizationFormData extends TlAccountAuthorizationFormAbstractData
{
    public function __construct(
    public int $flags,
    public array $requiredTypes,
    public array $values,
    public array $errors,
    public array $users,
    public ?string $privacyPolicyUrl,
    ) {
    }
}
