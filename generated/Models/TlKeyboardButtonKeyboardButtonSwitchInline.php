<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonSwitchInlinePeer_types;

/** Constructor model for keyboardButtonSwitchInline of KeyboardButton (crc32 991399fc). */
final class TlKeyboardButtonKeyboardButtonSwitchInline extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_keyboard_button_switch_inline';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'same_peer' => 'bool',
        'style' => 'string',
        'text' => 'string',
        'query' => 'string',
    ];

    public function peerTypes(): HasMany
    {
        return $this->tlChild(TlKeyboardButtonKeyboardButtonSwitchInlinePeer_types::class);
    }
}
