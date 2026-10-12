<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getThemes (crc32 7206e458), returns account.Themes. */
final class TlAccountGetThemesData extends Data
{
    public const METHOD = 'account.getThemes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $format,
    public int $hash,
    ) {
    }
}
