<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageRestriction_reason;

/** Constructor model for message of Message (crc32 7600b9d3). */
final class TlMessageMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'out' => 'bool',
        'mentioned' => 'bool',
        'media_unread' => 'bool',
        'silent' => 'bool',
        'post' => 'bool',
        'from_scheduled' => 'bool',
        'legacy' => 'bool',
        'edit_hide' => 'bool',
        'pinned' => 'bool',
        'noforwards' => 'bool',
        'invert_media' => 'bool',
        'flags2' => 'int',
        'offline' => 'bool',
        'video_processing_pending' => 'bool',
        'paid_suggested_post_stars' => 'bool',
        'paid_suggested_post_ton' => 'bool',
        'tl_id' => 'int',
        'from_id' => 'string',
        'from_boosts_applied' => 'int',
        'from_rank' => 'string',
        'peer_id' => 'string',
        'saved_peer_id' => 'string',
        'fwd_from' => 'string',
        'via_bot_id' => 'int',
        'via_business_bot_id' => 'int',
        'guestchat_via_from' => 'string',
        'reply_to' => 'string',
        'date' => 'int',
        'message' => 'string',
        'media' => 'string',
        'reply_markup' => 'string',
        'views' => 'int',
        'forwards' => 'int',
        'replies' => 'string',
        'edit_date' => 'int',
        'post_author' => 'string',
        'grouped_id' => 'int',
        'reactions' => 'string',
        'ttl_period' => 'int',
        'quick_reply_shortcut_id' => 'int',
        'effect' => 'int',
        'factcheck' => 'string',
        'report_delivery_until_date' => 'int',
        'paid_message_stars' => 'int',
        'suggested_post' => 'string',
        'schedule_repeat_period' => 'int',
        'summary_from_language' => 'string',
        'rich_message' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlMessageMessageEntities::class);
    }
    public function restrictionReason(): HasMany
    {
        return $this->tlChild(TlMessageMessageRestriction_reason::class);
    }
}
