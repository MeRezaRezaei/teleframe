<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionChatJoinedByLink of MessageAction (crc32 031224c3). */
final class TlMessageActionMessageActionChatJoinedByLink extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_chat_joined_by_link';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'inviter_id' => 'int',
    ];
}
