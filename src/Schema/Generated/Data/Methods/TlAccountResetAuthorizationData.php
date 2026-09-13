<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resetAuthorization (crc32 df77f3bc), returns Bool. */
final class TlAccountResetAuthorizationData extends Data
{
    public const METHOD = 'account.resetAuthorization';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
