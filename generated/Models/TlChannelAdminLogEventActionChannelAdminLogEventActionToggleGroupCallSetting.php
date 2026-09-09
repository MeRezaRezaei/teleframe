<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for channelAdminLogEventActionToggleGroupCallSetting of ChannelAdminLogEventAction (crc32 56d6a247). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_d6846eefc0e8';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function joinMuted(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'join_muted');
    }
}
