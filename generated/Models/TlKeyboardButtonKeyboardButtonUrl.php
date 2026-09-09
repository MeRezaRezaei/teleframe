<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonStyle;

/** Constructor model for keyboardButtonUrl of KeyboardButton (crc32 d80c25ec). */
final class TlKeyboardButtonKeyboardButtonUrl extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_keyboard_button_url';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'text' => 'string',
        'url' => 'string',
    ];

    public function style(): BelongsTo
    {
        return $this->belongsTo(TlKeyboardButtonStyle::class, 'style');
    }
}
