<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for chatBannedRights of ChatBannedRights (crc32 9f120418). */
final class TlChatBannedRightsChatBannedRights extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_banned_rights_chat_banned_rights';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'view_messages' => 'bool',
        'send_messages' => 'bool',
        'send_media' => 'bool',
        'send_stickers' => 'bool',
        'send_gifs' => 'bool',
        'send_games' => 'bool',
        'send_inline' => 'bool',
        'embed_links' => 'bool',
        'send_polls' => 'bool',
        'change_info' => 'bool',
        'invite_users' => 'bool',
        'pin_messages' => 'bool',
        'manage_topics' => 'bool',
        'send_photos' => 'bool',
        'send_videos' => 'bool',
        'send_roundvideos' => 'bool',
        'send_audios' => 'bool',
        'send_voices' => 'bool',
        'send_docs' => 'bool',
        'send_plain' => 'bool',
        'edit_rank' => 'bool',
        'send_reactions' => 'bool',
        'until_date' => 'int',
    ];
}
