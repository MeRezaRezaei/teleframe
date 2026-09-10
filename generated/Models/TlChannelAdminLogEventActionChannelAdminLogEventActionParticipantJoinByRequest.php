<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;

/** Constructor model for channelAdminLogEventActionParticipantJoinByRequest of ChannelAdminLogEventAction (crc32 afb6144a). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_80099d468567';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'approved_by' => 'int',
    ];

    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'invite');
    }
}
