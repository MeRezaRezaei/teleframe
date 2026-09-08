<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatInvitePeek of ChatInvite (crc32 61695cb0). */
final class TlChatInviteChatInvitePeek extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_invite_chat_invite_peek';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'chat' => 'string',
        'expires' => 'int',
    ];
}
