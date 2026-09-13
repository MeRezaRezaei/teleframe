<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setBotBroadcastDefaultAdminRights (crc32 788464e1), returns Bool. */
final class TlBotsSetBotBroadcastDefaultAdminRightsData extends Data
{
    public const METHOD = 'bots.setBotBroadcastDefaultAdminRights';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $adminRights,
    ) {
    }
}
