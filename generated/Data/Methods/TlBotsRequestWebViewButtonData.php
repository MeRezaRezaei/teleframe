<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.requestWebViewButton (crc32 31a2a35e), returns bots.RequestedButton. */
final class TlBotsRequestWebViewButtonData extends Data
{
    public const METHOD = 'bots.requestWebViewButton';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public mixed $button,
    ) {
    }
}
