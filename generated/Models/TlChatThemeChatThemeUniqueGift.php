<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatThemeChatThemeUniqueGiftTheme_settings;

/** Constructor model for chatThemeUniqueGift of ChatTheme (crc32 3458f9c8). */
final class TlChatThemeChatThemeUniqueGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_theme_chat_theme_unique_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'gift' => 'string',
    ];

    public function themeSettings(): HasMany
    {
        return $this->tlChild(TlChatThemeChatThemeUniqueGiftTheme_settings::class);
    }
}
