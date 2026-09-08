<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallStreamChannelsGroupCal7df01b0705a4Channels;

/** Constructor model for phone.groupCallStreamChannels of phone.GroupCallStreamChannels (crc32 d0e482b2). */
final class TlPhoneGroupCallStreamChannelsGroupCallStreamChannels extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_phone_group_call_stream_channels_group_cal_7df01b0705a4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function channels(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallStreamChannelsGroupCal7df01b0705a4Channels::class);
    }
}
