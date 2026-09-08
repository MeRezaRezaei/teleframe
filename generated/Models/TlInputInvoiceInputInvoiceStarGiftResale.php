<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputInvoiceStarGiftResale of InputInvoice (crc32 c39f5324). */
final class TlInputInvoiceInputInvoiceStarGiftResale extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_resale';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'ton' => 'bool',
        'slug' => 'string',
        'to_id' => 'string',
    ];
}
