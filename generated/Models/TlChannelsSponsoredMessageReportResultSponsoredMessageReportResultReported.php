<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channels.sponsoredMessageReportResultReported of channels.SponsoredMessageReportResult (crc32 ad798849). */
final class TlChannelsSponsoredMessageReportResultSponsoredMessageReportResultReported extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_sponsored_message_report_result_s_0c801a5ac42c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
