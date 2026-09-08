<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for reportResultReported of ReportResult (crc32 8db33c4b). */
final class TlReportResultReportResultReported extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_result_report_result_reported';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
