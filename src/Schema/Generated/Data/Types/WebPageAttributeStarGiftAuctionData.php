<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webPageAttributeStarGiftAuction of WebPageAttribute.
 */
final class WebPageAttributeStarGiftAuctionData extends TlWebPageAttributeAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public int $endDate,
    ) {
    }
}
