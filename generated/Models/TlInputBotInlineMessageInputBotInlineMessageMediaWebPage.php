<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineB2383747ff31Entities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;

/** Constructor model for inputBotInlineMessageMediaWebPage of InputBotInlineMessage (crc32 bddcc510). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaWebPage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__b2383747ff31';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'invert_media' => 'bool',
        'force_large_media' => 'bool',
        'force_small_media' => 'bool',
        'optional' => 'bool',
        'message' => 'string',
        'url' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlInputBotInlineMessageInputBotInlineB2383747ff31Entities::class);
    }

    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
}
