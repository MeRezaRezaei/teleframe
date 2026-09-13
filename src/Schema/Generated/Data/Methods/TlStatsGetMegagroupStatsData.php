<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getMegagroupStats (crc32 dcdf8607), returns stats.MegagroupStats. */
final class TlStatsGetMegagroupStatsData extends Data
{
    public const METHOD = 'stats.getMegagroupStats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public mixed $channel,
    ) {
    }
}
