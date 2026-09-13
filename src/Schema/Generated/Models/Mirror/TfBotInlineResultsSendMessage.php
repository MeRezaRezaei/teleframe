<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultsSendMessage extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'invert_media' => 'boolean',
        'no_webpage' => 'boolean',
        'heading' => 'integer',
        'period' => 'integer',
        'proximity_notification_radius' => 'integer',
        'shipping_address_requested' => 'boolean',
        'test' => 'boolean',
        'total_amount' => 'integer',
        'force_large_media' => 'boolean',
        'force_small_media' => 'boolean',
        'manual' => 'boolean',
        'safe' => 'boolean',
    ];
}
