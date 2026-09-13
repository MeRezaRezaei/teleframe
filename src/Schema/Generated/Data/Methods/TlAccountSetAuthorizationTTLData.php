<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setAuthorizationTTL (crc32 bf899aa0), returns Bool. */
final class TlAccountSetAuthorizationTTLData extends Data
{
    public const METHOD = 'account.setAuthorizationTTL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $authorizationTtlDays,
    ) {
    }
}
