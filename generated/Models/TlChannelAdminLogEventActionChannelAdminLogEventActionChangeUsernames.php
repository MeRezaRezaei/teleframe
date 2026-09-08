<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdm38f150219e2ePrev_value;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdm38f150219e2eNew_value;

/** Constructor model for channelAdminLogEventActionChangeUsernames of ChannelAdminLogEventAction (crc32 f04fb3a9). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeUsernames extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_38f150219e2e';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevValue(): HasMany
    {
        return $this->tlChild(TlChannelAdminLogEventActionChannelAdm38f150219e2ePrev_value::class);
    }
    public function newValue(): HasMany
    {
        return $this->tlChild(TlChannelAdminLogEventActionChannelAdm38f150219e2eNew_value::class);
    }
}
