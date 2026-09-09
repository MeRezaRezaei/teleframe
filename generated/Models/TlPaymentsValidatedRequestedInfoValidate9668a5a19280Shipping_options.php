<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param shipping_options (table tl_payments_validated_requested_info_validate_be6f170ec8df). */
final class TlPaymentsValidatedRequestedInfoValidate9668a5a19280Shipping_options extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_validated_requested_info_validate_be6f170ec8df';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
