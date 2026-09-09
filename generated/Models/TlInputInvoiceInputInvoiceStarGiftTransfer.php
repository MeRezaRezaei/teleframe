<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSavedStarGift;

/** Constructor model for inputInvoiceStarGiftTransfer of InputInvoice (crc32 4a5f5bd9). */
final class TlInputInvoiceInputInvoiceStarGiftTransfer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_transfer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function stargift(): BelongsTo
    {
        return $this->belongsTo(TlInputSavedStarGift::class, 'stargift');
    }
}
