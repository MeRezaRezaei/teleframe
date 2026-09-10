<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionsNotifySettingsReactionsNotifySettings;

/** Anchor model for TL type ReactionNotificationsFrom (spec §4.1). */
final class TlReactionNotificationsFrom extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_reaction_notifications_from_reaction_notif_70e6503a48b0';

    protected $guarded = [];

    public function messagesNotifyFrom(): HasMany
    {
        return $this->hasMany(TlReactionsNotifySettingsReactionsNotifySettings::class, 'messages_notify_from');
    }
    public function pollVotesNotifyFrom(): HasMany
    {
        return $this->hasMany(TlReactionsNotifySettingsReactionsNotifySettings::class, 'poll_votes_notify_from');
    }
    public function storiesNotifyFrom(): HasMany
    {
        return $this->hasMany(TlReactionsNotifySettingsReactionsNotifySettings::class, 'stories_notify_from');
    }
}
