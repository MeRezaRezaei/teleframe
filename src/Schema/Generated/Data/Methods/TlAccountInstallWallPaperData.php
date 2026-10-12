<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.installWallPaper (crc32 feed5769), returns Bool. */
final class TlAccountInstallWallPaperData extends Data
{
    public const METHOD = 'account.installWallPaper';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $wallpaper,
    public mixed $settings,
    ) {
    }
}
