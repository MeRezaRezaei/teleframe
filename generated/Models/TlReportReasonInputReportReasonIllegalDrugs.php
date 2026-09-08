<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputReportReasonIllegalDrugs of ReportReason (crc32 0a8eb2be). */
final class TlReportReasonInputReportReasonIllegalDrugs extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_reason_input_report_reason_illegal_drugs';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
