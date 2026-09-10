<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputReportReasonGeoIrrelevant of ReportReason (crc32 dbd4feed). */
final class TlReportReasonInputReportReasonGeoIrrelevant extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_report_reason_input_report_reason_geo_irrelevant';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
