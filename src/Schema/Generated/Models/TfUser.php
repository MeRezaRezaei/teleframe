<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Telegram user — tf_users.
 *
 * PK: (id, account_id). id is the globally unique Telegram user ID.
 * Telegram user IDs are positive 64-bit integers.
 */
class TfUser extends TfModel
{
    protected $table = 'tf_users';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'first_name', 'last_name',
        'username', 'phone', 'photo_id', 'status_type', 'lang_code',
        'restriction_reason', 'bot_info_version', 'bot_inline_placeholder',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_self' => 'boolean',
        'is_bot' => 'boolean',
        'is_contact' => 'boolean',
        'is_mutual_contact' => 'boolean',
        'is_deleted' => 'boolean',
        'is_verified' => 'boolean',
        'is_restricted' => 'boolean',
        'is_premium' => 'boolean',
        'is_scam' => 'boolean',
        'is_fake' => 'boolean',
    ];

    // -- Relations --

    /** Messages sent by this user (across all peers). */
    public function messages(): HasMany
    {
        return $this->hasMany(TfMessage::class, 'from_id', 'id');
    }

    /** Channel participations. */
    public function channelParticipations(): HasMany
    {
        return $this->hasMany(TfChannelParticipant::class, 'user_id', 'id');
    }
}
