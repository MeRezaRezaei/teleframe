<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param values (table tl_account_authorization_form_authorization_form__values). */
final class TlAccountAuthorizationFormAuthorizationFormValues extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_authorization_form_authorization_form__values';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
