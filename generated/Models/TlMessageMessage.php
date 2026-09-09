<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFactCheck;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageFwdHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageRestriction_reason;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplies;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSuggestedPost;

/** Constructor model for message of Message (crc32 7600b9d3). */
final class TlMessageMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

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
        'from_boosts_applied' => 'int',
        'from_rank' => 'string',
        'via_bot_id' => 'int',
        'via_business_bot_id' => 'int',
        'date' => 'int',
        'message' => 'string',
        'views' => 'int',
        'forwards' => 'int',
        'edit_date' => 'int',
        'post_author' => 'string',
        'grouped_id' => 'int',
        'ttl_period' => 'int',
        'quick_reply_shortcut_id' => 'int',
        'effect' => 'int',
        'report_delivery_until_date' => 'int',
        'paid_message_stars' => 'int',
        'schedule_repeat_period' => 'int',
        'summary_from_language' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlMessageMessageEntities::class);
    }
    public function restrictionReason(): HasMany
    {
        return $this->tlChild(TlMessageMessageRestriction_reason::class);
    }

    public function fwdFrom(): BelongsTo
    {
        return $this->belongsTo(TlMessageFwdHeader::class, 'fwd_from');
    }
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TlMessageReplyHeader::class, 'reply_to');
    }
    public function media(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'media');
    }
    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
    public function replies(): BelongsTo
    {
        return $this->belongsTo(TlMessageReplies::class, 'replies');
    }
    public function reactions(): BelongsTo
    {
        return $this->belongsTo(TlMessageReactions::class, 'reactions');
    }
    public function factcheck(): BelongsTo
    {
        return $this->belongsTo(TlFactCheck::class, 'factcheck');
    }
    public function suggestedPost(): BelongsTo
    {
        return $this->belongsTo(TlSuggestedPost::class, 'suggested_post');
    }
    public function richMessage(): BelongsTo
    {
        return $this->belongsTo(TlRichMessage::class, 'rich_message');
    }
}
