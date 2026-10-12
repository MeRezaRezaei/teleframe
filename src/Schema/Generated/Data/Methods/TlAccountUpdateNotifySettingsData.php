<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateNotifySettings (crc32 84be5b93), returns Bool. */
final class TlAccountUpdateNotifySettingsData extends Data
{
    public const METHOD = 'account.updateNotifySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $settings,
    ) {
    }
}
