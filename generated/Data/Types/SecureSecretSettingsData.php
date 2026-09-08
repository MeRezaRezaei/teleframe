<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureSecretSettings of SecureSecretSettings.
 *
 * bytes params carried as base64 strings: secure_secret
 */
final class SecureSecretSettingsData extends TlSecureSecretSettingsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecurePasswordKdfAlgoAbstractData $secureAlgo,
    public string $secureSecret,
    public int $secureSecretId,
    ) {
    }
}
