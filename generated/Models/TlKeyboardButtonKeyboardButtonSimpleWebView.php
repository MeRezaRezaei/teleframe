<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for keyboardButtonSimpleWebView of KeyboardButton (crc32 e15c4370). */
final class TlKeyboardButtonKeyboardButtonSimpleWebView extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_keyboard_button_keyboard_button_simple_web_view';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'style' => 'string',
        'text' => 'string',
        'url' => 'string',
    ];
}
