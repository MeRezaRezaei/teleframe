<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.allowSendMessage (crc32 f132e3ef), returns Updates. */
final class TlBotsAllowSendMessageData extends Data
{
    public const METHOD = 'bots.allowSendMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    ) {
    }
}
