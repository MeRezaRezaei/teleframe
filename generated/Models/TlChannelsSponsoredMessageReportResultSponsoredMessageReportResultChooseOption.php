<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsSponsoredMessageReportResultS90d28813b853Options;

/** Constructor model for channels.sponsoredMessageReportResultChooseOption of channels.SponsoredMessageReportResult (crc32 846f9e42). */
final class TlChannelsSponsoredMessageReportResultSponsoredMessageReportResultChooseOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_sponsored_message_report_result_s_90d28813b853';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
    ];

    public function options(): HasMany
    {
        return $this->tlChild(TlChannelsSponsoredMessageReportResultS90d28813b853Options::class);
    }
}
