<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_users`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 19.
 */
final class TlUser extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_users';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, id),
     * which the database enforces; `id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'constructor' => 'string',
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

    /** Declared foreign key (1:N): `tf_users_access_hash`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersAccessHash(): HasMany
    {
        return $this->hasMany(TfUsersAccessHash::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_active_users`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotActiveUsers(): HasMany
    {
        return $this->hasMany(TfUsersBotActiveUsers::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_info_version`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotInfoVersion(): HasMany
    {
        return $this->hasMany(TfUsersBotInfoVersion::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_inline_placeholder`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotInlinePlaceholder(): HasMany
    {
        return $this->hasMany(TfUsersBotInlinePlaceholder::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_verification_icon`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotVerificationIcon(): HasMany
    {
        return $this->hasMany(TfUsersBotVerificationIcon::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_color`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersColor(): HasMany
    {
        return $this->hasMany(TfUsersColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_emoji_status`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersEmojiStatus(): HasMany
    {
        return $this->hasMany(TfUsersEmojiStatus::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_first_name`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersFirstName(): HasMany
    {
        return $this->hasMany(TfUsersFirstName::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_lang_code`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersLangCode(): HasMany
    {
        return $this->hasMany(TfUsersLangCode::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_last_name`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersLastName(): HasMany
    {
        return $this->hasMany(TfUsersLastName::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_phone`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersPhone(): HasMany
    {
        return $this->hasMany(TfUsersPhone::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_photo`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersPhoto(): HasMany
    {
        return $this->hasMany(TfUsersPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_profile_color`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersProfileColor(): HasMany
    {
        return $this->hasMany(TfUsersProfileColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_restriction_reason`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersRestrictionReason(): HasMany
    {
        return $this->hasMany(TfUsersRestrictionReason::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_send_paid_messages_stars`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersSendPaidMessagesStars(): HasMany
    {
        return $this->hasMany(TfUsersSendPaidMessagesStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_status`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersStatus(): HasMany
    {
        return $this->hasMany(TfUsersStatus::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_stories_max_id`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersStoriesMaxId(): HasMany
    {
        return $this->hasMany(TfUsersStoriesMaxId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_username`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersUsername(): HasMany
    {
        return $this->hasMany(TfUsersUsername::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_usernames`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersUsernames(): HasMany
    {
        return $this->hasMany(TfUsersUsernames::class, 'id', 'id');
    }
}
