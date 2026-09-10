<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessage;

/** Constructor model for inputBotInlineResultGame of InputBotInlineResult (crc32 4fa417f2). */
final class TlInputBotInlineResultInputBotInlineResultGame extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_result_input_bot_inline_result_game';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
        'short_name' => 'string',
    ];

    public function sendMessage(): BelongsTo
    {
        return $this->belongsTo(TlInputBotInlineMessage::class, 'send_message');
    }
}
