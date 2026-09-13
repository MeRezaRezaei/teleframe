<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getTheme (crc32 3a5869ec), returns Theme. */
final class TlAccountGetThemeData extends Data
{
    public const METHOD = 'account.getTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $format,
    public mixed $theme,
    ) {
    }
}
