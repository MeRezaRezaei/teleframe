<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getBotBusinessConnection (crc32 76a86270), returns Updates. */
final class TlAccountGetBotBusinessConnectionData extends Data
{
    public const METHOD = 'account.getBotBusinessConnection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $connectionId,
    ) {
    }
}
