<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatAdminRights of ChatAdminRights (crc32 5fb224d5). */
final class TlChatAdminRightsChatAdminRights extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_admin_rights_chat_admin_rights';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'change_info' => 'bool',
        'post_messages' => 'bool',
        'edit_messages' => 'bool',
        'delete_messages' => 'bool',
        'ban_users' => 'bool',
        'invite_users' => 'bool',
        'pin_messages' => 'bool',
        'add_admins' => 'bool',
        'anonymous' => 'bool',
        'manage_call' => 'bool',
        'other' => 'bool',
        'manage_topics' => 'bool',
        'post_stories' => 'bool',
        'edit_stories' => 'bool',
        'delete_stories' => 'bool',
        'manage_direct_messages' => 'bool',
        'manage_ranks' => 'bool',
    ];
}
