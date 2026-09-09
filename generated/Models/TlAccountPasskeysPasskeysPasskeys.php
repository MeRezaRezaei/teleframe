<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param passkeys (table tl_account_passkeys_passkeys__passkeys). */
final class TlAccountPasskeysPasskeysPasskeys extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_passkeys_passkeys__passkeys';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
