<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botCommandScopePeerUser of BotCommandScope (crc32 0a1321f3). */
final class TlBotCommandScopeBotCommandScopePeerUser extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_command_scope_bot_command_scope_peer_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'user_id' => 'string',
    ];
}
