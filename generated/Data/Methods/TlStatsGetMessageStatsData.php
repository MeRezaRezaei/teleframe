<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getMessageStats (crc32 b6e0a3f5), returns stats.MessageStats. */
final class TlStatsGetMessageStatsData extends Data
{
    public const METHOD = 'stats.getMessageStats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public mixed $channel,
    public int $msgId,
    ) {
    }
}
