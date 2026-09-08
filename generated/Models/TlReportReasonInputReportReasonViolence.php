<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputReportReasonViolence of ReportReason (crc32 1e22c78d). */
final class TlReportReasonInputReportReasonViolence extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_reason_input_report_reason_violence';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
