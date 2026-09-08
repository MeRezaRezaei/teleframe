<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.declinePasswordReset (crc32 4c9409f6), returns Bool. */
final class TlAccountDeclinePasswordResetData extends Data
{
    public const METHOD = 'account.declinePasswordReset';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
