<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror channel (Chat union: channel / channelForbidden ctors).
 *
 * Table tf_channels — the peer FK target for peer_type 3. Composite key
 * (account_id, id); optional wire facts live in tf_channels_* 1:1 children
 * (row existence = fact existence), vectors in positioned 1:N children.
 *
 * title + access_hash + date are inline (title/access_hash required by
 * channelForbidden, date carried on the channel ctor); the two ctors never
 * fill orthogonal facts in one wire row, so child row presence disambiguates.
 *
 * Cross-domain relations and the Task-8 FK wiring are added once all peer
 * target tables land.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property string $title
 * @property int $access_hash
 * @property int $date
 * @property bool $creator
 * @property bool $left
 * @property bool $broadcast
 * @property bool $verified
 * @property bool $megagroup
 * @property bool $restricted
 * @property bool $signatures
 * @property bool $min
 * @property bool $scam
 * @property bool $has_link
 * @property bool $has_geo
 * @property bool $slowmode_enabled
 * @property bool $call_active
 * @property bool $call_not_empty
 * @property bool $fake
 * @property bool $gigagroup
 * @property bool $noforwards
 * @property bool $join_to_send
 * @property bool $join_request
 * @property bool $forum
 * @property bool $stories_hidden
 * @property bool $stories_hidden_min
 * @property bool $stories_unavailable
 * @property bool $signature_profiles
 * @property bool $autotranslation
 * @property bool $broadcast_messages_allowed
 * @property bool $monoforum
 * @property bool $forum_tabs
 */
final class TfChannel extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_channels';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'date' => 'integer',
        'creator' => 'boolean',
        'left' => 'boolean',
        'broadcast' => 'boolean',
        'verified' => 'boolean',
        'megagroup' => 'boolean',
        'restricted' => 'boolean',
        'signatures' => 'boolean',
        'min' => 'boolean',
        'scam' => 'boolean',
        'has_link' => 'boolean',
        'has_geo' => 'boolean',
        'slowmode_enabled' => 'boolean',
        'call_active' => 'boolean',
        'call_not_empty' => 'boolean',
        'fake' => 'boolean',
        'gigagroup' => 'boolean',
        'noforwards' => 'boolean',
        'join_to_send' => 'boolean',
        'join_request' => 'boolean',
        'forum' => 'boolean',
        'stories_hidden' => 'boolean',
        'stories_hidden_min' => 'boolean',
        'stories_unavailable' => 'boolean',
        'signature_profiles' => 'boolean',
        'autotranslation' => 'boolean',
        'broadcast_messages_allowed' => 'boolean',
        'monoforum' => 'boolean',
        'forum_tabs' => 'boolean',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
        ];
    }

    public function username(): HasOne
    {
        return $this->hasOne(TfChannelsUsername::class, 'id', 'id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(TfChannelsPhoto::class, 'id', 'id');
    }

    public function restrictionReasons(): HasMany
    {
        return $this->hasMany(TfChannelsRestrictionReason::class, 'id', 'id');
    }

    public function adminRights(): HasOne
    {
        return $this->hasOne(TfChannelsAdminRights::class, 'id', 'id');
    }

    public function bannedRights(): HasOne
    {
        return $this->hasOne(TfChannelsBannedRights::class, 'id', 'id');
    }

    public function defaultBannedRights(): HasOne
    {
        return $this->hasOne(TfChannelsDefaultBannedRights::class, 'id', 'id');
    }

    public function participantsCount(): HasOne
    {
        return $this->hasOne(TfChannelsParticipantsCount::class, 'id', 'id');
    }

    public function usernames(): HasMany
    {
        return $this->hasMany(TfChannelsUsernames::class, 'id', 'id');
    }

    public function storiesMaxId(): HasOne
    {
        return $this->hasOne(TfChannelsStoriesMaxId::class, 'id', 'id');
    }

    public function color(): HasOne
    {
        return $this->hasOne(TfChannelsColor::class, 'id', 'id');
    }

    public function profileColor(): HasOne
    {
        return $this->hasOne(TfChannelsProfileColor::class, 'id', 'id');
    }

    public function emojiStatus(): HasOne
    {
        return $this->hasOne(TfChannelsEmojiStatus::class, 'id', 'id');
    }

    public function level(): HasOne
    {
        return $this->hasOne(TfChannelsLevel::class, 'id', 'id');
    }

    public function subscriptionUntilDate(): HasOne
    {
        return $this->hasOne(TfChannelsSubscriptionUntilDate::class, 'id', 'id');
    }

    public function botVerificationIcon(): HasOne
    {
        return $this->hasOne(TfChannelsBotVerificationIcon::class, 'id', 'id');
    }

    public function sendPaidMessagesStars(): HasOne
    {
        return $this->hasOne(TfChannelsSendPaidMessagesStars::class, 'id', 'id');
    }

    public function linkedMonoforumId(): HasOne
    {
        return $this->hasOne(TfChannelsLinkedMonoforumId::class, 'id', 'id');
    }

    public function untilDate(): HasOne
    {
        return $this->hasOne(TfChannelsUntilDate::class, 'id', 'id');
    }
}
