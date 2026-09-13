<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.readHistory (crc32 cc104937), returns Bool. */
final class TlChannelsReadHistoryData extends Data
{
    public const METHOD = 'channels.readHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public int $maxId,
    ) {
    }
}
