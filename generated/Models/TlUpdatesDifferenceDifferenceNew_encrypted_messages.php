<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param new_encrypted_messages (table tl_updates_difference_difference__new_encrypted_messages). */
final class TlUpdatesDifferenceDifferenceNew_encrypted_messages extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_updates_difference_difference__new_encrypted_messages';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
