<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputInvoiceStarGiftPrepaidUpgrade of InputInvoice (crc32 9a0b48b8). */
final class TlInputInvoiceInputInvoiceStarGiftPrepaidUpgrade extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_prepaid_upgrade';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'string',
    ];
}
