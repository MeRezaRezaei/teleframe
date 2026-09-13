<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getGiveawayInfo (crc32 f4239425), returns payments.GiveawayInfo. */
final class TlPaymentsGetGiveawayInfoData extends Data
{
    public const METHOD = 'payments.getGiveawayInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    ) {
    }
}
