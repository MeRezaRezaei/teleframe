<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param types (table tl_message_action_message_action_secure_values_sent__types). */
final class TlMessageActionMessageActionSecureValuesSentTypes extends TlAnchorModel
{
    protected $table = 'tl_message_action_message_action_secure_values_sent__types';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
