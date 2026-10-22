<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.invokeWebViewCustomMethod (crc32 087fc5e7), returns DataJSON. */
final class TlBotsInvokeWebViewCustomMethodData extends Data
{
    public const METHOD = 'bots.invokeWebViewCustomMethod';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $customMethod,
    public mixed $params,
    ) {
    }
}
