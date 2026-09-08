<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotsAttachMenuBotsBots;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotsAttachMenuBotsUsers;

/** Constructor model for attachMenuBots of AttachMenuBots (crc32 3c4301c0). */
final class TlAttachMenuBotsAttachMenuBots extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_attach_menu_bots_attach_menu_bots';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function bots(): HasMany
    {
        return $this->tlChild(TlAttachMenuBotsAttachMenuBotsBots::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAttachMenuBotsAttachMenuBotsUsers::class);
    }
}
