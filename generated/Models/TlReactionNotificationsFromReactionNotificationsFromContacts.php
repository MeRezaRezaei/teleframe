<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for reactionNotificationsFromContacts of ReactionNotificationsFrom (crc32 bac3a61a). */
final class TlReactionNotificationsFromReactionNotificationsFromContacts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_reaction_notifications_from_reaction_notif_d9fdd2611884';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
