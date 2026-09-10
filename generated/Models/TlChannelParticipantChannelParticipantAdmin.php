<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminRights;

/** Constructor model for channelParticipantAdmin of ChannelParticipant (crc32 34c3bb53). */
final class TlChannelParticipantChannelParticipantAdmin extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_participant_channel_participant_admin';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_edit' => 'bool',
        'self' => 'bool',
        'user_id' => 'int',
        'inviter_id' => 'int',
        'promoted_by' => 'int',
        'date' => 'int',
        'rank' => 'string',
    ];

    public function adminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'admin_rights');
    }
}
