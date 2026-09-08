<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for fragment.collectibleInfo of fragment.CollectibleInfo.
 */
final class TlFragmentCollectibleInfoData extends TlFragmentCollectibleInfoAbstractData
{
    public function __construct(
    public int $purchaseDate,
    public string $currency,
    public int $amount,
    public string $cryptoCurrency,
    public int $cryptoAmount,
    public string $url,
    ) {
    }
}
