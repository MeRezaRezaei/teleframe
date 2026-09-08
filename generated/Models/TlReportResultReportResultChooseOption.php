<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReportResultReportResultChooseOptionOptions;

/** Constructor model for reportResultChooseOption of ReportResult (crc32 f0e4e0b6). */
final class TlReportResultReportResultChooseOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_result_report_result_choose_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
    ];

    public function options(): HasMany
    {
        return $this->tlChild(TlReportResultReportResultChooseOptionOptions::class);
    }
}
