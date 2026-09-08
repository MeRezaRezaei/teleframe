<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputKeyboardButtonRequestPeer of KeyboardButton (crc32 02b78156). */
final class TlKeyboardButtonInputKeyboardButtonRequestPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_input_keyboard_button_request_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_requested' => 'bool',
        'username_requested' => 'bool',
        'photo_requested' => 'bool',
        'style' => 'string',
        'text' => 'string',
        'button_id' => 'int',
        'peer_type' => 'string',
        'max_quantity' => 'int',
    ];
}
