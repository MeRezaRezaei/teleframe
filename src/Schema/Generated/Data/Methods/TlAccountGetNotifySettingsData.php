<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getNotifySettings (crc32 12b3ad31), returns PeerNotifySettings. */
final class TlAccountGetNotifySettingsData extends Data
{
    public const METHOD = 'account.getNotifySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
