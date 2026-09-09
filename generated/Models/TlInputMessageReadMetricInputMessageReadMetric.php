<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputMessageReadMetric of InputMessageReadMetric (crc32 402b4495). */
final class TlInputMessageReadMetricInputMessageReadMetric extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_message_read_metric_input_message_read_metric';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'view_id' => 'int',
        'time_in_view_ms' => 'int',
        'active_time_in_view_ms' => 'int',
        'height_to_viewport_ratio_permille' => 'int',
        'seen_range_ratio_permille' => 'int',
    ];
}
