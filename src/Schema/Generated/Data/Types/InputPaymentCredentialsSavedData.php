<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPaymentCredentialsSaved of InputPaymentCredentials.
 *
 * bytes params carried as base64 strings: tmp_password
 */
final class InputPaymentCredentialsSavedData extends TlInputPaymentCredentialsAbstractData
{
    public function __construct(
    public string $id,
    public string $tmpPassword,
    ) {
    }
}
