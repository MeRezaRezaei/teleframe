<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftUpgradeAttributesStarB00cb34f5cf4Attributes;

/** Constructor model for payments.starGiftUpgradeAttributes of payments.StarGiftUpgradeAttributes (crc32 46c6e36f). */
final class TlPaymentsStarGiftUpgradeAttributesStarGiftUpgradeAttributes extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_star_gift_upgrade_attributes_star_b00cb34f5cf4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftUpgradeAttributesStarB00cb34f5cf4Attributes::class);
    }
}
