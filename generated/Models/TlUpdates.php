<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteJoinResultChatInviteJoinResultOk;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesInvitedUsersInvitedUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentResultPaymentResult;

/** Anchor model for TL type Updates (spec §4.1). */
final class TlUpdates extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_updates_update_short';

    protected $guarded = [];

    public function updates(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentResultPaymentResult::class, 'updates');
    }
    public function updatesMessagesChatInviteJoinResultOk(): HasMany
    {
        return $this->hasMany(TlMessagesChatInviteJoinResultChatInviteJoinResultOk::class, 'updates');
    }
    public function updatesMessagesInvitedUsers(): HasMany
    {
        return $this->hasMany(TlMessagesInvitedUsersInvitedUsers::class, 'updates');
    }
}
