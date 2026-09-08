<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for keyboardButtonUserProfile of KeyboardButton (crc32 c0fd5d09). */
final class TlKeyboardButtonKeyboardButtonUserProfile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_keyboard_button_user_profile';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'style' => 'string',
        'text' => 'string',
        'user_id' => 'int',
    ];
}
