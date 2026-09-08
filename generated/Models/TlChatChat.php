<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chat of Chat (crc32 41cbf256). */
final class TlChatChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'left' => 'bool',
        'deactivated' => 'bool',
        'call_active' => 'bool',
        'call_not_empty' => 'bool',
        'noforwards' => 'bool',
        'tl_id' => 'int',
        'title' => 'string',
        'photo' => 'string',
        'participants_count' => 'int',
        'date' => 'int',
        'version' => 'int',
        'migrated_to' => 'string',
        'admin_rights' => 'string',
        'default_banned_rights' => 'string',
    ];
}
