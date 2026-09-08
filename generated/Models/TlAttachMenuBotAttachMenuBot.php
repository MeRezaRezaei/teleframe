<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotAttachMenuBotPeer_types;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotAttachMenuBotIcons;

/** Constructor model for attachMenuBot of AttachMenuBot (crc32 d90d8dfe). */
final class TlAttachMenuBotAttachMenuBot extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_attach_menu_bot_attach_menu_bot';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inactive' => 'bool',
        'has_settings' => 'bool',
        'request_write_access' => 'bool',
        'show_in_attach_menu' => 'bool',
        'show_in_side_menu' => 'bool',
        'side_menu_disclaimer_needed' => 'bool',
        'bot_id' => 'int',
        'short_name' => 'string',
    ];

    public function peerTypes(): HasMany
    {
        return $this->tlChild(TlAttachMenuBotAttachMenuBotPeer_types::class);
    }
    public function icons(): HasMany
    {
        return $this->tlChild(TlAttachMenuBotAttachMenuBotIcons::class);
    }
}
