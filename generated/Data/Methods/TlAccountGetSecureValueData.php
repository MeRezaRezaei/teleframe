<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getSecureValue (crc32 73665bc2), returns Vector<SecureValue>. */
final class TlAccountGetSecureValueData extends Data
{
    public const METHOD = 'account.getSecureValue';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $types,
    ) {
    }
}
