<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for reactionNotificationsFromAll of ReactionNotificationsFrom (crc32 4b9e22a0). */
final class TlReactionNotificationsFromReactionNotificationsFromAll extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_reaction_notifications_from_reaction_notif_70e6503a48b0';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
