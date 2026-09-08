<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.checkDownloadFileParams (crc32 50077589), returns Bool. */
final class TlBotsCheckDownloadFileParamsData extends Data
{
    public const METHOD = 'bots.checkDownloadFileParams';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $fileName,
    public string $url,
    ) {
    }
}
