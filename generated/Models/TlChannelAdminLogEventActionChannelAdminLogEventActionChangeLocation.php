<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelLocation;

/** Constructor model for channelAdminLogEventActionChangeLocation of ChannelAdminLogEventAction (crc32 0e6b76ae). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_6b0307fdb532';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevValue(): BelongsTo
    {
        return $this->belongsTo(TlChannelLocation::class, 'prev_value');
    }
    public function newValue(): BelongsTo
    {
        return $this->belongsTo(TlChannelLocation::class, 'new_value');
    }
}
