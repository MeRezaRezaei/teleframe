<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror user (User union: userEmpty / user ctor).
 *
 * Table tf_users — the peer FK target for peer_type 1. Composite key
 * (account_id, id); optional wire facts live in tf_users_* 1:1 children
 * (row existence = fact existence), vectors in positioned 1:N children.
 *
 * Cross-domain relations and the Task-8 FK wiring are added once all peer
 * target tables land.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $self
 * @property bool $contact
 * @property bool $mutual_contact
 * @property bool $deleted
 * @property bool $bot
 * @property bool $bot_chat_history
 * @property bool $bot_nochats
 * @property bool $verified
 * @property bool $restricted
 * @property bool $min
 * @property bool $bot_inline_geo
 * @property bool $support
 * @property bool $scam
 * @property bool $apply_min_photo
 * @property bool $fake
 * @property bool $bot_attach_menu
 * @property bool $premium
 * @property bool $attach_menu_enabled
 * @property bool $bot_can_edit
 * @property bool $close_friend
 * @property bool $stories_hidden
 * @property bool $stories_unavailable
 * @property bool $contact_require_premium
 * @property bool $bot_business
 * @property bool $bot_has_main_app
 * @property bool $bot_forum_view
 * @property bool $bot_forum_can_manage_topics
 * @property bool $bot_can_manage_bots
 * @property bool $bot_guestchat
 * @property bool $bot_guard
 */
final class TfUser extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_users';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'self' => 'boolean',
        'contact' => 'boolean',
        'mutual_contact' => 'boolean',
        'deleted' => 'boolean',
        'bot' => 'boolean',
        'bot_chat_history' => 'boolean',
        'bot_nochats' => 'boolean',
        'verified' => 'boolean',
        'restricted' => 'boolean',
        'min' => 'boolean',
        'bot_inline_geo' => 'boolean',
        'support' => 'boolean',
        'scam' => 'boolean',
        'apply_min_photo' => 'boolean',
        'fake' => 'boolean',
        'bot_attach_menu' => 'boolean',
        'premium' => 'boolean',
        'attach_menu_enabled' => 'boolean',
        'bot_can_edit' => 'boolean',
        'close_friend' => 'boolean',
        'stories_hidden' => 'boolean',
        'stories_unavailable' => 'boolean',
        'contact_require_premium' => 'boolean',
        'bot_business' => 'boolean',
        'bot_has_main_app' => 'boolean',
        'bot_forum_view' => 'boolean',
        'bot_forum_can_manage_topics' => 'boolean',
        'bot_can_manage_bots' => 'boolean',
        'bot_guestchat' => 'boolean',
        'bot_guard' => 'boolean',
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

    public function accessHash(): HasOne
    {
        return $this->hasOne(TfUsersAccessHash::class, 'id', 'id');
    }

    public function firstName(): HasOne
    {
        return $this->hasOne(TfUsersFirstName::class, 'id', 'id');
    }

    public function lastName(): HasOne
    {
        return $this->hasOne(TfUsersLastName::class, 'id', 'id');
    }

    public function username(): HasOne
    {
        return $this->hasOne(TfUsersUsername::class, 'id', 'id');
    }

    public function phone(): HasOne
    {
        return $this->hasOne(TfUsersPhone::class, 'id', 'id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(TfUsersPhoto::class, 'id', 'id');
    }

    public function status(): HasOne
    {
        return $this->hasOne(TfUsersStatus::class, 'id', 'id');
    }

    public function botInfoVersion(): HasOne
    {
        return $this->hasOne(TfUsersBotInfoVersion::class, 'id', 'id');
    }

    public function restrictionReasons(): HasMany
    {
        return $this->hasMany(TfUsersRestrictionReason::class, 'id', 'id');
    }

    public function botInlinePlaceholder(): HasOne
    {
        return $this->hasOne(TfUsersBotInlinePlaceholder::class, 'id', 'id');
    }

    public function langCode(): HasOne
    {
        return $this->hasOne(TfUsersLangCode::class, 'id', 'id');
    }

    public function emojiStatus(): HasOne
    {
        return $this->hasOne(TfUsersEmojiStatus::class, 'id', 'id');
    }

    public function usernames(): HasMany
    {
        return $this->hasMany(TfUsersUsernames::class, 'id', 'id');
    }

    public function storiesMaxId(): HasOne
    {
        return $this->hasOne(TfUsersStoriesMaxId::class, 'id', 'id');
    }

    public function color(): HasOne
    {
        return $this->hasOne(TfUsersColor::class, 'id', 'id');
    }

    public function profileColor(): HasOne
    {
        return $this->hasOne(TfUsersProfileColor::class, 'id', 'id');
    }

    public function botActiveUsers(): HasOne
    {
        return $this->hasOne(TfUsersBotActiveUsers::class, 'id', 'id');
    }

    public function botVerificationIcon(): HasOne
    {
        return $this->hasOne(TfUsersBotVerificationIcon::class, 'id', 'id');
    }

    public function sendPaidMessagesStars(): HasOne
    {
        return $this->hasOne(TfUsersSendPaidMessagesStars::class, 'id', 'id');
    }
}
