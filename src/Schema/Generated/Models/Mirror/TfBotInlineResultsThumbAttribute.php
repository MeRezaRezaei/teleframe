<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultsThumbAttribute extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_thumb_attributes';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'w' => 'integer',
        'h' => 'integer',
        'mask' => 'boolean',
        'round_message' => 'boolean',
        'supports_streaming' => 'boolean',
        'nosound' => 'boolean',
        'duration' => 'float',
        'preload_prefix_size' => 'integer',
        'video_start_ts' => 'float',
        'voice' => 'boolean',
        'free' => 'boolean',
        'text_color' => 'boolean',
    ];
}
