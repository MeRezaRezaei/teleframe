<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftDropOriginalDetails;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftTransfer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftUpgrade;

/** Anchor model for TL type InputSavedStarGift (spec §4.1). */
final class TlInputSavedStarGift extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_saved_star_gift_input_saved_star_gift_chat';

    protected $guarded = [];

    public function stargift(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStarGiftUpgrade::class, 'stargift');
    }
    public function stargiftInputInvoiceStarGiftDropOriginalDetails(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStarGiftDropOriginalDetails::class, 'stargift');
    }
    public function stargiftInputInvoiceStarGiftTransfer(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStarGiftTransfer::class, 'stargift');
    }
}
