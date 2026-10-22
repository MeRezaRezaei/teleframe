<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftUpgradePreview (crc32 9c9abcb1), returns payments.StarGiftUpgradePreview. */
final class TlPaymentsGetStarGiftUpgradePreviewData extends Data
{
    public const METHOD = 'payments.getStarGiftUpgradePreview';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $giftId,
    ) {
    }
}
