<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for channelAdminLogEventActionChangePhoto of ChannelAdminLogEventAction (crc32 434bd2af). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_1d1939e936ae';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'prev_photo');
    }
    public function newPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'new_photo');
    }
}
