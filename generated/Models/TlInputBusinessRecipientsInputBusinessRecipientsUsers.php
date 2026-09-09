<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_input_business_recipients_input_business_r_f3ebd21efedf). */
final class TlInputBusinessRecipientsInputBusinessRecipientsUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_business_recipients_input_business_r_f3ebd21efedf';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
