<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.checkUsername (crc32 87f2219b), returns Bool. */
final class TlBotsCheckUsernameData extends Data
{
    public const METHOD = 'bots.checkUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $username,
    ) {
    }
}
