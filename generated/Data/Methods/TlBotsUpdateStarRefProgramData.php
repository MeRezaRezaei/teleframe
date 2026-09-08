<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.updateStarRefProgram (crc32 778b5ab3), returns StarRefProgram. */
final class TlBotsUpdateStarRefProgramData extends Data
{
    public const METHOD = 'bots.updateStarRefProgram';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $bot,
    public int $commissionPermille,
    public ?int $durationMonths,
    ) {
    }
}
