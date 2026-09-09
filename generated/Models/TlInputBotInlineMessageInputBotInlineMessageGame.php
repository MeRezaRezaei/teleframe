<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;

/** Constructor model for inputBotInlineMessageGame of InputBotInlineMessage (crc32 4b425864). */
final class TlInputBotInlineMessageInputBotInlineMessageGame extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline_message_game';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
}
