<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.createTheme (crc32 652e4400), returns Theme. */
final class TlAccountCreateThemeData extends Data
{
    public const METHOD = 'account.createTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $slug,
    public string $title,
    public mixed $document,
    public ?array $settings,
    ) {
    }
}
