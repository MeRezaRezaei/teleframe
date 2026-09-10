<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventChannelAdminLogEvent;

/** Anchor model for TL type ChannelAdminLogEventAction (spec §4.1). */
final class TlChannelAdminLogEventAction extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_a48f273010c3';

    protected $guarded = [];

    public function action(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventChannelAdminLogEvent::class, 'action');
    }
}
