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

/** Constructor model for keyboardButtonCopy of KeyboardButton (crc32 bcc4af10). */
final class TlKeyboardButtonKeyboardButtonCopy extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_keyboard_button_copy';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'text' => 'string',
        'copy_text' => 'string',
    ];

    public function style(): BelongsTo
    {
        return $this->belongsTo(TlKeyboardButtonStyle::class, 'style');
    }
}
