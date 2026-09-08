<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.updateSettings (crc32 093fa0bf), returns Bool. */
final class TlSmsjobsUpdateSettingsData extends Data
{
    public const METHOD = 'smsjobs.updateSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $allowInternational,
    ) {
    }
}
