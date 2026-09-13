<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getRecentMeUrls (crc32 3dc0f114), returns help.RecentMeUrls. */
final class TlHelpGetRecentMeUrlsData extends Data
{
    public const METHOD = 'help.getRecentMeUrls';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $referer,
    ) {
    }
}
