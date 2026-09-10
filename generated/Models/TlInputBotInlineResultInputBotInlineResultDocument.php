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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDocument;

/** Constructor model for inputBotInlineResultDocument of InputBotInlineResult (crc32 fff8fdc4). */
final class TlInputBotInlineResultInputBotInlineResultDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'string',
        'tl_type' => 'string',
        'title' => 'string',
        'description' => 'string',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlInputDocument::class, 'document');
    }
    public function sendMessage(): BelongsTo
    {
        return $this->belongsTo(TlInputBotInlineMessage::class, 'send_message');
    }
}
