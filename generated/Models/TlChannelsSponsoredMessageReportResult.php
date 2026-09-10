<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type channels.SponsoredMessageReportResult (spec §4.1). */
final class TlChannelsSponsoredMessageReportResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_sponsored_message_report_result_s_a8bb878b93c6';

    protected $guarded = [];
}
