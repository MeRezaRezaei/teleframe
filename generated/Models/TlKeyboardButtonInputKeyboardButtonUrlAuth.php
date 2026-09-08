<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputKeyboardButtonUrlAuth of KeyboardButton (crc32 68013e72). */
final class TlKeyboardButtonInputKeyboardButtonUrlAuth extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_input_keyboard_button_url_auth';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'request_write_access' => 'bool',
        'style' => 'string',
        'text' => 'string',
        'fwd_text' => 'string',
        'url' => 'string',
        'bot' => 'string',
    ];
}
