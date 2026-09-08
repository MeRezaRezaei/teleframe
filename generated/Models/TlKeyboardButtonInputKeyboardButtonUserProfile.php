<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputKeyboardButtonUserProfile of KeyboardButton (crc32 7d5e07c7). */
final class TlKeyboardButtonInputKeyboardButtonUserProfile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_input_keyboard_button_user_profile';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'style' => 'string',
        'text' => 'string',
        'user_id' => 'string',
    ];
}
