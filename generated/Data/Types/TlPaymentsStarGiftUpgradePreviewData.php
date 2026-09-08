<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starGiftUpgradePreview of payments.StarGiftUpgradePreview.
 */
final class TlPaymentsStarGiftUpgradePreviewData extends TlPaymentsStarGiftUpgradePreviewAbstractData
{
    public function __construct(
    public array $sampleAttributes,
    public array $prices,
    public array $nextPrices,
    ) {
    }
}
