<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannel;

/** Constructor model for chat of Chat (crc32 41cbf256). */
final class TlChatChat extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'participants_count' => 'int',
        'date' => 'int',
        'version' => 'int',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlChatPhoto::class, 'photo');
    }
    public function migratedTo(): BelongsTo
    {
        return $this->belongsTo(TlInputChannel::class, 'migrated_to');
    }
    public function adminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'admin_rights');
    }
    public function defaultBannedRights(): BelongsTo
    {
        return $this->belongsTo(TlChatBannedRights::class, 'default_banned_rights');
    }
}
