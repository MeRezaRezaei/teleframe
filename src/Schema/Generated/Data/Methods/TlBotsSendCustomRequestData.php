<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.sendCustomRequest (crc32 aa2769ed), returns DataJSON. */
final class TlBotsSendCustomRequestData extends Data
{
    public const METHOD = 'bots.sendCustomRequest';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $customMethod,
    public mixed $params,
    ) {
    }
}
