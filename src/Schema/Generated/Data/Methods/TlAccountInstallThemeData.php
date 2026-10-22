<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.installTheme (crc32 c727bb3b), returns Bool. */
final class TlAccountInstallThemeData extends Data
{
    public const METHOD = 'account.installTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public mixed $theme,
    public ?string $format,
    public mixed $baseTheme,
    ) {
    }
}
