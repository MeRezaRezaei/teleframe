<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.checkCanSendGift (crc32 c0c4edc9), returns payments.CheckCanSendGiftResult. */
final class TlPaymentsCheckCanSendGiftData extends Data
{
    public const METHOD = 'payments.checkCanSendGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $giftId,
    ) {
    }
}
