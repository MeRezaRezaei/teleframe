<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.disablePeerConnectedBot (crc32 5e437ed9), returns Bool. */
final class TlAccountDisablePeerConnectedBotData extends Data
{
    public const METHOD = 'account.disablePeerConnectedBot';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
