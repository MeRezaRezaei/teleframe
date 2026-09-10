<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type payments.StarGiftUpgradeAttributes (spec §4.1). */
final class TlPaymentsStarGiftUpgradeAttributes extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_upgrade_attributes_star_b00cb34f5cf4';

    protected $guarded = [];
}
