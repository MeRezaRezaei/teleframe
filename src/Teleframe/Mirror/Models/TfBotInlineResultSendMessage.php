<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 send_message child — the required BotInlineMessage union (9 ctors),
 * flattened; geo/reply_markup/entities/photo/rich_message nested objects are
 * deferred.
 */
final class TfBotInlineResultSendMessage extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'int',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
        'no_webpage' => 'bool',
        'invert_media' => 'bool',
        'shipping_address_requested' => 'bool',
        'test' => 'bool',
        'force_large_media' => 'bool',
        'force_small_media' => 'bool',
        'manual' => 'bool',
        'safe' => 'bool',
    ];

    public function inlineResult(): BelongsTo
    {
        return $this->belongsTo(TfBotInlineResult::class, 'id', 'id');
    }
}
