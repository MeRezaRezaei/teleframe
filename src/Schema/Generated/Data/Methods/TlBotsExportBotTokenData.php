<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.exportBotToken (crc32 bd0d99eb), returns bots.ExportedBotToken. */
final class TlBotsExportBotTokenData extends Data
{
    public const METHOD = 'bots.exportBotToken';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public mixed $revoke,
    ) {
    }
}
