<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRights;

/** Constructor model for channelParticipantBanned of ChannelParticipant (crc32 d5f0ad91). */
final class TlChannelParticipantChannelParticipantBanned extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_channel_participant_channel_participant_banned';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'left' => 'bool',
        'kicked_by' => 'int',
        'date' => 'int',
        'rank' => 'string',
    ];

    public function bannedRights(): BelongsTo
    {
        return $this->belongsTo(TlChatBannedRights::class, 'banned_rights');
    }
}
