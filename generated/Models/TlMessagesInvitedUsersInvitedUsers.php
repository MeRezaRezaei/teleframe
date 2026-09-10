<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesInvitedUsersInvitedUsersMissing_invitees;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdates;

/** Constructor model for messages.invitedUsers of messages.InvitedUsers (crc32 7f5defa6). */
final class TlMessagesInvitedUsersInvitedUsers extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_invited_users_invited_users';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function missingInvitees(): HasMany
    {
        return $this->tlChild(TlMessagesInvitedUsersInvitedUsersMissing_invitees::class);
    }

    public function updates(): BelongsTo
    {
        return $this->belongsTo(TlUpdates::class, 'updates');
    }
}
