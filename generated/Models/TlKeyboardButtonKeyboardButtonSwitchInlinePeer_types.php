<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param peer_types (table tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03). */
final class TlKeyboardButtonKeyboardButtonSwitchInlinePeer_types extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
