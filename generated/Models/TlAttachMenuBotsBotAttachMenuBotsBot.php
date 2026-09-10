<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBot;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotsBotAttachMenuBotsBotUsers;

/** Constructor model for attachMenuBotsBot of AttachMenuBotsBot (crc32 93bf667f). */
final class TlAttachMenuBotsBotAttachMenuBotsBot extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_attach_menu_bots_bot_attach_menu_bots_bot';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlAttachMenuBotsBotAttachMenuBotsBotUsers::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TlAttachMenuBot::class, 'bot');
    }
}
