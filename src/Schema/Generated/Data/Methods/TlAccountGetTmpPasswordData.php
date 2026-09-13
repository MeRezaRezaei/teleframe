<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getTmpPassword (crc32 449e0b51), returns account.TmpPassword. */
final class TlAccountGetTmpPasswordData extends Data
{
    public const METHOD = 'account.getTmpPassword';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $password,
    public int $period,
    ) {
    }
}
