<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.passwordInputSettings of account.PasswordInputSettings.
 *
 * bytes params carried as base64 strings: new_password_hash
 */
final class TlAccountPasswordInputSettingsData extends TlAccountPasswordInputSettingsAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPasswordKdfAlgoAbstractData $newAlgo,
    public ?string $newPasswordHash,
    public ?string $hint,
    public ?string $email,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureSecretSettingsAbstractData $newSecureSettings,
    ) {
    }
}
