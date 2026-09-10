<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;

/** Anchor model for TL type ChannelLocation (spec §4.1). */
final class TlChannelLocation extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channel_location_channel_location';

    protected $guarded = [];

    public function location(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'location');
    }
    public function newValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeLocation::class, 'new_value');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeLocation::class, 'prev_value');
    }
}
