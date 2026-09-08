<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.dropTempAuthKeys (crc32 8e48a188), returns Bool. */
final class TlAuthDropTempAuthKeysData extends Data
{
    public const METHOD = 'auth.dropTempAuthKeys';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $exceptAuthKeys,
    ) {
    }
}
