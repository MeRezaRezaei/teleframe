<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for premiumGiftCodeOption of PremiumGiftCodeOption.
 */
final class PremiumGiftCodeOptionData extends TlPremiumGiftCodeOptionAbstractData
{
    public function __construct(
    public int $flags,
    public int $users,
    public int $months,
    public ?string $storeProduct,
    public ?int $storeQuantity,
    public string $currency,
    public int $amount,
    ) {
    }
}
