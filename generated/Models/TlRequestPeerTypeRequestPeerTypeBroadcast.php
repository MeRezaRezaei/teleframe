<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminRights;

/** Constructor model for requestPeerTypeBroadcast of RequestPeerType (crc32 339bef6c). */
final class TlRequestPeerTypeRequestPeerTypeBroadcast extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_request_peer_type_request_peer_type_broadcast';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
    ];

    public function hasUsername(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'has_username');
    }
    public function userAdminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'user_admin_rights');
    }
    public function botAdminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'bot_admin_rights');
    }
}
