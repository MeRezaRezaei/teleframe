<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror content message (Message union: messageEmpty / message ctor).
 *
 * The MessageConsumerFamily service payloads land in TfMessageService; the
 * two tables share (account_id, peer_type, peer_id, id) by construction so
 * MessagesUnion can UNION ALL over both (disjoint telegram id spaces).
 *
 * Cross-domain peer relations (peer → TfUser / TfChat / TfChannel keyed by
 * peer_type 1/2/3) resolve against the identity worktree models and belong to
 * the Task-8 FK wiring — they are added there once tf_users / tf_chats /
 * tf_channels land; directions are documented in the messages-domain plan.
 */
final class TfMessage extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'date' => 'integer',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'from_scheduled' => 'boolean',
        'legacy' => 'boolean',
        'edit_hide' => 'boolean',
        'pinned' => 'boolean',
        'noforwards' => 'boolean',
        'invert_media' => 'boolean',
        'offline' => 'boolean',
        'video_processing_pending' => 'boolean',
        'paid_suggested_post_stars' => 'boolean',
        'paid_suggested_post_ton' => 'boolean',
    ];

    public function from(): HasOne
    {
        return $this->hasOne(TfMessageFromId::class, 'id', 'id');
    }

    public function savedPeer(): HasOne
    {
        return $this->hasOne(TfMessageSavedPeerId::class, 'id', 'id');
    }

    public function fwdFrom(): HasOne
    {
        return $this->hasOne(TfMessageFwdFrom::class, 'id', 'id');
    }

    public function replyTo(): HasOne
    {
        return $this->hasOne(TfMessageReplyTo::class, 'id', 'id');
    }

    public function media(): HasOne
    {
        return $this->hasOne(TfMessageMedia::class, 'id', 'id');
    }

    public function replyMarkup(): HasOne
    {
        return $this->hasOne(TfMessageReplyMarkup::class, 'id', 'id');
    }

    public function entities(): HasMany
    {
        return $this->hasMany(TfMessageEntity::class, 'id', 'id');
    }

    public function views(): HasOne
    {
        return $this->hasOne(TfMessageViews::class, 'id', 'id');
    }

    public function forwards(): HasOne
    {
        return $this->hasOne(TfMessageForwards::class, 'id', 'id');
    }

    public function replies(): HasOne
    {
        return $this->hasOne(TfMessageReplies::class, 'id', 'id');
    }

    public function reactions(): HasOne
    {
        return $this->hasOne(TfMessageReactions::class, 'id', 'id');
    }

    public function factCheck(): HasOne
    {
        return $this->hasOne(TfMessageFactCheck::class, 'id', 'id');
    }

    public function suggestedPost(): HasOne
    {
        return $this->hasOne(TfMessageSuggestedPost::class, 'id', 'id');
    }
}
