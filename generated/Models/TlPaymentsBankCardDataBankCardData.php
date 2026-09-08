<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsBankCardDataBankCardDataOpen_urls;

/** Constructor model for payments.bankCardData of payments.BankCardData (crc32 3e24e573). */
final class TlPaymentsBankCardDataBankCardData extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_bank_card_data_bank_card_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
    ];

    public function openUrls(): HasMany
    {
        return $this->tlChild(TlPaymentsBankCardDataBankCardDataOpen_urls::class);
    }
}
