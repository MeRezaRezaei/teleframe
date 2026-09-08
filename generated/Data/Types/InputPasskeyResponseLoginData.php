<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPasskeyResponseLogin of InputPasskeyResponse.
 *
 * bytes params carried as base64 strings: authenticator_data, signature
 */
final class InputPasskeyResponseLoginData extends TlInputPasskeyResponseAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $clientData,
    public string $authenticatorData,
    public string $signature,
    public string $userHandle,
    ) {
    }
}
