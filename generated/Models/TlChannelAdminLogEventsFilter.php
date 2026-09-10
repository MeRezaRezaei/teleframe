<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type ChannelAdminLogEventsFilter (spec §4.1). */
final class TlChannelAdminLogEventsFilter extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_events_filter_channel_ad_2d07b3f742d8';

    protected $guarded = [];
}
