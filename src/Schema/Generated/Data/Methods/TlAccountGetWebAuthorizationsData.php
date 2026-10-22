<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getWebAuthorizations (crc32 182e6d6f), returns account.WebAuthorizations. */
final class TlAccountGetWebAuthorizationsData extends Data
{
    public const METHOD = 'account.getWebAuthorizations';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
