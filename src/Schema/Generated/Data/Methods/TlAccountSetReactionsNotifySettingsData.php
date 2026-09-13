<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setReactionsNotifySettings (crc32 316ce548), returns ReactionsNotifySettings. */
final class TlAccountSetReactionsNotifySettingsData extends Data
{
    public const METHOD = 'account.setReactionsNotifySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $settings,
    ) {
    }
}
