<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param saved_credentials (table tl_payments_payment_form_payment_form__saved_credentials). */
final class TlPaymentsPaymentFormPaymentFormSaved_credentials extends TlAnchorModel
{
    protected $table = 'tl_payments_payment_form_payment_form__saved_credentials';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
