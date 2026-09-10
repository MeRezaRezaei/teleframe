<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageFwdHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortChatMessageEntities;

/** Constructor model for updateShortChatMessage of Updates (crc32 4d6deea5). */
final class TlUpdatesUpdateShortChatMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_update_short_chat_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'out' => 'bool',
        'mentioned' => 'bool',
        'media_unread' => 'bool',
        'silent' => 'bool',
        'tl_id' => 'int',
        'from_id' => 'int',
        'chat_id' => 'int',
        'message' => 'string',
        'pts' => 'int',
        'pts_count' => 'int',
        'date' => 'int',
        'via_bot_id' => 'int',
        'ttl_period' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdateShortChatMessageEntities::class);
    }

    public function fwdFrom(): BelongsTo
    {
        return $this->belongsTo(TlMessageFwdHeader::class, 'fwd_from');
    }
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TlMessageReplyHeader::class, 'reply_to');
    }
}
