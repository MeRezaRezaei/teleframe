<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.invalidateSignInCodes (crc32 ca8ae8ba), returns Bool. */
final class TlAccountInvalidateSignInCodesData extends Data
{
    public const METHOD = 'account.invalidateSignInCodes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $codes,
    ) {
    }
}
