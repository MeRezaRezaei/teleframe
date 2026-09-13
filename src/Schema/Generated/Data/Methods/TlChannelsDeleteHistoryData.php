<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.deleteHistory (crc32 9baa9647), returns Updates. */
final class TlChannelsDeleteHistoryData extends Data
{
    public const METHOD = 'channels.deleteHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forEveryone,
    public mixed $channel,
    public int $maxId,
    ) {
    }
}
