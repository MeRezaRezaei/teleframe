<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getPremiumGiftCodeOptions (crc32 2757ba54), returns Vector<PremiumGiftCodeOption>. */
final class TlPaymentsGetPremiumGiftCodeOptionsData extends Data
{
    public const METHOD = 'payments.getPremiumGiftCodeOptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $boostPeer,
    ) {
    }
}
