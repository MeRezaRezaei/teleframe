<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveTheme (crc32 f257106c), returns Bool. */
final class TlAccountSaveThemeData extends Data
{
    public const METHOD = 'account.saveTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $theme,
    public mixed $unsave,
    ) {
    }
}
