<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resetWebAuthorizations (crc32 682d2594), returns Bool. */
final class TlAccountResetWebAuthorizationsData extends Data
{
    public const METHOD = 'account.resetWebAuthorizations';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
