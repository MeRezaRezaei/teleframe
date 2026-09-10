<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for channelAdminLogEventActionToggleSignatures of ChannelAdminLogEventAction (crc32 26ae0971). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_1280d69ef5fa';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function newValue(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'new_value');
    }
}
