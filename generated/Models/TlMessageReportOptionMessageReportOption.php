<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageReportOption of MessageReportOption (crc32 7903e3d9). */
final class TlMessageReportOptionMessageReportOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_report_option_message_report_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'text' => 'string',
        'option' => 'string',
    ];
}
