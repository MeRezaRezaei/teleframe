<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setContentSettings (crc32 b574b16b), returns Bool. */
final class TlAccountSetContentSettingsData extends Data
{
    public const METHOD = 'account.setContentSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $sensitiveEnabled,
    ) {
    }
}
