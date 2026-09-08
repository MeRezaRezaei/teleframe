<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputInvoiceStarGiftAuctionBid of InputInvoice (crc32 1ecafa10). */
final class TlInputInvoiceInputInvoiceStarGiftAuctionBid extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_auction_bid';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'hide_name' => 'bool',
        'update_bid' => 'bool',
        'peer' => 'string',
        'gift_id' => 'int',
        'bid_amount' => 'int',
        'message' => 'string',
    ];
}
