<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;

/** Constructor model for botCommandScopePeerUser of BotCommandScope (crc32 0a1321f3). */
final class TlBotCommandScopeBotCommandScopePeerUser extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_bot_command_scope_bot_command_scope_peer_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'user_id');
    }
}
