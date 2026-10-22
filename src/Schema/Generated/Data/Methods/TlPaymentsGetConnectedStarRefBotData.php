<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getConnectedStarRefBot (crc32 b7d998f0), returns payments.ConnectedStarRefBots. */
final class TlPaymentsGetConnectedStarRefBotData extends Data
{
    public const METHOD = 'payments.getConnectedStarRefBot';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $bot,
    ) {
    }
}
