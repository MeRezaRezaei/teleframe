<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getInlineBotResults (crc32 514e999d), returns messages.BotResults. */
final class TlMessagesGetInlineBotResultsData extends Data
{
    public const METHOD = 'messages.getInlineBotResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $bot,
    public mixed $peer,
    public mixed $geoPoint,
    public string $query,
    public string $offset,
    ) {
    }
}
