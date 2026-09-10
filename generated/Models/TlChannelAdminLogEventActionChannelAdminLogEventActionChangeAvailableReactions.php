<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatReactions;

/** Constructor model for channelAdminLogEventActionChangeAvailableReactions of ChannelAdminLogEventAction (crc32 be4e0ef8). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeAvailableReactions extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_73ca016088c9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevValue(): BelongsTo
    {
        return $this->belongsTo(TlChatReactions::class, 'prev_value');
    }
    public function newValue(): BelongsTo
    {
        return $this->belongsTo(TlChatReactions::class, 'new_value');
    }
}
