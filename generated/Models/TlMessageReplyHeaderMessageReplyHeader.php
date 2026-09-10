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
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageFwdHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeaderMessageReplyHeaderQuote_entities;

/** Constructor model for messageReplyHeader of MessageReplyHeader (crc32 1b97dd66). */
final class TlMessageReplyHeaderMessageReplyHeader extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_reply_header_message_reply_header';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'reply_to_scheduled' => 'bool',
        'forum_topic' => 'bool',
        'quote' => 'bool',
        'reply_to_ephemeral' => 'bool',
        'reply_to_msg_id' => 'int',
        'reply_to_top_id' => 'int',
        'quote_text' => 'string',
        'quote_offset' => 'int',
        'todo_item_id' => 'int',
        'poll_option' => 'string',
    ];

    public function quoteEntities(): HasMany
    {
        return $this->tlChild(TlMessageReplyHeaderMessageReplyHeaderQuote_entities::class);
    }

    public function replyFrom(): BelongsTo
    {
        return $this->belongsTo(TlMessageFwdHeader::class, 'reply_from');
    }
    public function replyMedia(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'reply_media');
    }
}
