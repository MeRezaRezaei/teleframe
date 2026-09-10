<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonStyle;

/** Constructor model for inputKeyboardButtonUserProfile of KeyboardButton (crc32 7d5e07c7). */
final class TlKeyboardButtonInputKeyboardButtonUserProfile extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_input_keyboard_button_user_profile';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'text' => 'string',
    ];

    public function style(): BelongsTo
    {
        return $this->belongsTo(TlKeyboardButtonStyle::class, 'style');
    }
    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'user_id');
    }
}
