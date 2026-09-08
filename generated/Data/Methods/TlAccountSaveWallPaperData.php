<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveWallPaper (crc32 6c5a5b37), returns Bool. */
final class TlAccountSaveWallPaperData extends Data
{
    public const METHOD = 'account.saveWallPaper';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $wallpaper,
    public mixed $unsave,
    public mixed $settings,
    ) {
    }
}
