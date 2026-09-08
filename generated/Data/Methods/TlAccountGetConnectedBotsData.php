<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getConnectedBots (crc32 4ea4c80f), returns account.ConnectedBots. */
final class TlAccountGetConnectedBotsData extends Data
{
    public const METHOD = 'account.getConnectedBots';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
