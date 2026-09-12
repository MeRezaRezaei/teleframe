<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Telegram basic group (chat) — tf_chats.
 *
 * PK: (id, account_id). id is the globally unique Telegram chat ID.
 * Telegram chat IDs are positive 64-bit integers.
 */
class TfChat extends TfModel
{
    protected $table = 'tf_chats';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'title', 'photo_id',
        'date', 'version', 'participants_count', 'ttl_period',
        'admin_count', 'deld_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_creator' => 'boolean',
        'is_kicked' => 'boolean',
        'is_deactivated' => 'boolean',
        'is_call_active' => 'boolean',
        'is_noforwards' => 'boolean',
    ];

    // -- Relations --

    /** Messages in this chat. */
    public function messages(): HasMany
    {
        return $this->hasMany(TfMessage::class, 'peer_id', 'id');
    }
}
