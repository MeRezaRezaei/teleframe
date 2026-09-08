<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputReportReasonPornography of ReportReason (crc32 2e59d922). */
final class TlReportReasonInputReportReasonPornography extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_reason_input_report_reason_pornography';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
