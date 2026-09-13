<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateBirthday (crc32 cc6e0c11), returns Bool. */
final class TlAccountUpdateBirthdayData extends Data
{
    public const METHOD = 'account.updateBirthday';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $birthday,
    ) {
    }
}
