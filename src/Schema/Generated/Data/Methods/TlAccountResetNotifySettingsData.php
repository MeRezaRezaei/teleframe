<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resetNotifySettings (crc32 db7e1747), returns Bool. */
final class TlAccountResetNotifySettingsData extends Data
{
    public const METHOD = 'account.resetNotifySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
