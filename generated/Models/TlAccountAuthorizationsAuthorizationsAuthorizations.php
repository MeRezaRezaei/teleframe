<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param authorizations (table tl_account_authorizations_authorizations__authorizations). */
final class TlAccountAuthorizationsAuthorizationsAuthorizations extends TlAnchorModel
{
    protected $table = 'tl_account_authorizations_authorizations__authorizations';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
