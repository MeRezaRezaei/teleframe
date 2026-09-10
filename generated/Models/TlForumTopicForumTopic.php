<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettings;

/** Constructor model for forumTopic of ForumTopic (crc32 fcdad815). */
final class TlForumTopicForumTopic extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_forum_topic_forum_topic';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'my' => 'bool',
        'closed' => 'bool',
        'pinned' => 'bool',
        'short' => 'bool',
        'hidden' => 'bool',
        'title_missing' => 'bool',
        'tl_id' => 'int',
        'date' => 'int',
        'title' => 'string',
        'icon_color' => 'int',
        'icon_emoji_id' => 'int',
        'top_message' => 'int',
        'read_inbox_max_id' => 'int',
        'read_outbox_max_id' => 'int',
        'unread_count' => 'int',
        'unread_mentions_count' => 'int',
        'unread_reactions_count' => 'int',
        'unread_poll_votes_count' => 'int',
    ];

    public function notifySettings(): BelongsTo
    {
        return $this->belongsTo(TlPeerNotifySettings::class, 'notify_settings');
    }
    public function draft(): BelongsTo
    {
        return $this->belongsTo(TlDraftMessage::class, 'draft');
    }
}
