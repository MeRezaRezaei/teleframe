<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSetChatTheme;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type ChatTheme (spec §4.1). */
final class TlChatTheme extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_theme';

    protected $guarded = [];

    public function theme(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSetChatTheme::class, 'theme');
    }
    public function themeUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'theme');
    }
}
