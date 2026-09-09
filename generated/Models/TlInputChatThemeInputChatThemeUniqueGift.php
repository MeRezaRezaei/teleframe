<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputChatThemeUniqueGift of InputChatTheme (crc32 87e5dfe4). */
final class TlInputChatThemeInputChatThemeUniqueGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_chat_theme_input_chat_theme_unique_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'slug' => 'string',
    ];
}
