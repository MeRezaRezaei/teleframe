<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getPollStats (crc32 c27dfa68), returns stats.PollStats. */
final class TlStatsGetPollStatsData extends Data
{
    public const METHOD = 'stats.getPollStats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public mixed $peer,
    public int $msgId,
    ) {
    }
}
