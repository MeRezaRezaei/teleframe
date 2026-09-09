<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param values (table tl_message_action_message_action_secure_value_135a50e48e86). */
final class TlMessageActionMessageActionSecureValuesSentMeValues extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_secure_value_135a50e48e86';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
