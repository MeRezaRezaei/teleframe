<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for botCommandScopeChatAdmins of BotCommandScope (crc32 b9aa606a). */
final class TlBotCommandScopeBotCommandScopeChatAdmins extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bot_command_scope_bot_command_scope_chat_admins';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
