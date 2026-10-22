<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getAppUpdate (crc32 522d5a7d), returns help.AppUpdate. */
final class TlHelpGetAppUpdateData extends Data
{
    public const METHOD = 'help.getAppUpdate';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $source,
    ) {
    }
}
