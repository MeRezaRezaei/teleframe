<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceNew_messages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceOther_updates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceUsers;

/** Constructor model for updates.channelDifference of updates.ChannelDifference (crc32 2064674e). */
final class TlUpdatesChannelDifferenceChannelDifference extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_channel_difference_channel_difference';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'final' => 'bool',
        'pts' => 'int',
        'timeout' => 'int',
    ];

    public function newMessages(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceNew_messages::class);
    }
    public function otherUpdates(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceOther_updates::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceUsers::class);
    }
}
