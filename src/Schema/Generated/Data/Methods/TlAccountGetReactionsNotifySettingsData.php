<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getReactionsNotifySettings (crc32 06dd654c), returns ReactionsNotifySettings. */
final class TlAccountGetReactionsNotifySettingsData extends Data
{
    public const METHOD = 'account.getReactionsNotifySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
