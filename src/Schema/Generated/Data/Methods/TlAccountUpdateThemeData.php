<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateTheme (crc32 2bf40ccc), returns Theme. */
final class TlAccountUpdateThemeData extends Data
{
    public const METHOD = 'account.updateTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $format,
    public mixed $theme,
    public ?string $slug,
    public ?string $title,
    public mixed $document,
    public ?array $settings,
    ) {
    }
}
