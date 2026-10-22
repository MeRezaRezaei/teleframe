<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveSecureValue (crc32 899fe31d), returns SecureValue. */
final class TlAccountSaveSecureValueData extends Data
{
    public const METHOD = 'account.saveSecureValue';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $value,
    public int $secureSecretId,
    ) {
    }
}
