<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.toggleUsername (crc32 58d6b376), returns Bool. */
final class TlAccountToggleUsernameData extends Data
{
    public const METHOD = 'account.toggleUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $username,
    public mixed $active,
    ) {
    }
}
