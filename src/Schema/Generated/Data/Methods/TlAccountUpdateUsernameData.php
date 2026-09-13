<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateUsername (crc32 3e0bdd7c), returns User. */
final class TlAccountUpdateUsernameData extends Data
{
    public const METHOD = 'account.updateUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $username,
    ) {
    }
}
