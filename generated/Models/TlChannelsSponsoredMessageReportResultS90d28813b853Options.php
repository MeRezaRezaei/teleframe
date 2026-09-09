<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param options (table tl_channels_sponsored_message_report_result_s_4f877f3b1319). */
final class TlChannelsSponsoredMessageReportResultS90d28813b853Options extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_sponsored_message_report_result_s_4f877f3b1319';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
