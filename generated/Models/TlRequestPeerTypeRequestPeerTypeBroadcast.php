<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for requestPeerTypeBroadcast of RequestPeerType (crc32 339bef6c). */
final class TlRequestPeerTypeRequestPeerTypeBroadcast extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_request_peer_type_request_peer_type_broadcast';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'has_username' => 'string',
        'user_admin_rights' => 'string',
        'bot_admin_rights' => 'string',
    ];
}
