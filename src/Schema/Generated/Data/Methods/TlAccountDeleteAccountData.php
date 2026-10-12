<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.deleteAccount (crc32 a2c0cf74), returns Bool. */
final class TlAccountDeleteAccountData extends Data
{
    public const METHOD = 'account.deleteAccount';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $reason,
    public mixed $password,
    ) {
    }
}
