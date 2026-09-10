<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dNext_prices;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dPrices;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dSample_attributes;

/** Constructor model for payments.starGiftUpgradePreview of payments.StarGiftUpgradePreview (crc32 3de1dfed). */
final class TlPaymentsStarGiftUpgradePreviewStarGiftUpgradePreview extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function sampleAttributes(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dSample_attributes::class);
    }
    public function prices(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dPrices::class);
    }
    public function nextPrices(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftUpgradePreviewStarGi2469e890a24dNext_prices::class);
    }
}
