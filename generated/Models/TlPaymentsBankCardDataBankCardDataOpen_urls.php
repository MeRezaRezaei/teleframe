<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param open_urls (table tl_payments_bank_card_data_bank_card_data__open_urls). */
final class TlPaymentsBankCardDataBankCardDataOpen_urls extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_bank_card_data_bank_card_data__open_urls';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
