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

/** Constructor model for channelAdminLogEventActionExportedInviteEdit of ChannelAdminLogEventAction (crc32 e90ebb59). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_88f46dc9c3bc';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevInvite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'prev_invite');
    }
    public function newInvite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'new_invite');
    }
}
