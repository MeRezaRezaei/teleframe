<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getRequestedWebViewButton (crc32 bf25b7f3), returns KeyboardButton. */
final class TlBotsGetRequestedWebViewButtonData extends Data
{
    public const METHOD = 'bots.getRequestedWebViewButton';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $webappReqId,
    ) {
    }
}
