<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.confirmBotConnection (crc32 67ed1f68), returns Bool. */
final class TlAccountConfirmBotConnectionData extends Data
{
    public const METHOD = 'account.confirmBotConnection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $botId,
    ) {
    }
}
