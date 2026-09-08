<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setBotInfo (crc32 10cf3123), returns Bool. */
final class TlBotsSetBotInfoData extends Data
{
    public const METHOD = 'bots.setBotInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $bot,
    public string $langCode,
    public ?string $name,
    public ?string $about,
    public ?string $description,
    ) {
    }
}
