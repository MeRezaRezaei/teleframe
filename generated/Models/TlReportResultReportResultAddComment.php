<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for reportResultAddComment of ReportResult (crc32 6f09ac31). */
final class TlReportResultReportResultAddComment extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_report_result_report_result_add_comment';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'optional' => 'bool',
        'option' => 'string',
    ];
}
