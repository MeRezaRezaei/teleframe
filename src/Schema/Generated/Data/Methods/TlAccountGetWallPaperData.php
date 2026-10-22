<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getWallPaper (crc32 fc8ddbea), returns WallPaper. */
final class TlAccountGetWallPaperData extends Data
{
    public const METHOD = 'account.getWallPaper';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $wallpaper,
    ) {
    }
}
