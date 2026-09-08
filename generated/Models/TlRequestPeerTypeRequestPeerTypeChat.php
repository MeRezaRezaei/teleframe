<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for requestPeerTypeChat of RequestPeerType (crc32 c9f06e1b). */
final class TlRequestPeerTypeRequestPeerTypeChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_request_peer_type_request_peer_type_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'bot_participant' => 'bool',
        'has_username' => 'string',
        'forum' => 'string',
        'user_admin_rights' => 'string',
        'bot_admin_rights' => 'string',
    ];
}
