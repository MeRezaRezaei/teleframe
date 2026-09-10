<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoiceInvoicePrices;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoiceInvoiceSuggested_tip_amounts;

/** Constructor model for invoice of Invoice (crc32 049ee584). */
final class TlInvoiceInvoice extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_invoice_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'test' => 'bool',
        'name_requested' => 'bool',
        'phone_requested' => 'bool',
        'email_requested' => 'bool',
        'shipping_address_requested' => 'bool',
        'flexible' => 'bool',
        'phone_to_provider' => 'bool',
        'email_to_provider' => 'bool',
        'recurring' => 'bool',
        'currency' => 'string',
        'max_tip_amount' => 'int',
        'terms_url' => 'string',
        'subscription_period' => 'int',
    ];

    public function prices(): HasMany
    {
        return $this->tlChild(TlInvoiceInvoicePrices::class);
    }
    public function suggestedTipAmounts(): HasMany
    {
        return $this->tlChild(TlInvoiceInvoiceSuggested_tip_amounts::class);
    }
}
