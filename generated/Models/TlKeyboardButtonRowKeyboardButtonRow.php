<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonRowKeyboardButtonRowButtons;

/** Constructor model for keyboardButtonRow of KeyboardButtonRow (crc32 77608b83). */
final class TlKeyboardButtonRowKeyboardButtonRow extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_row_keyboard_button_row';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function buttons(): HasMany
    {
        return $this->tlChild(TlKeyboardButtonRowKeyboardButtonRowButtons::class);
    }
}
