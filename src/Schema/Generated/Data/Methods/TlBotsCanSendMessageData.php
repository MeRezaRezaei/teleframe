<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.canSendMessage (crc32 1359f4e6), returns Bool. */
final class TlBotsCanSendMessageData extends Data
{
    public const METHOD = 'bots.canSendMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    ) {
    }
}
