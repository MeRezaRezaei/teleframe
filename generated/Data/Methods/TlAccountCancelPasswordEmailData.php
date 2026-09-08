<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.cancelPasswordEmail (crc32 c1cbd5b6), returns Bool. */
final class TlAccountCancelPasswordEmailData extends Data
{
    public const METHOD = 'account.cancelPasswordEmail';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
