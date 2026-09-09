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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaAutoEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;

/** Constructor model for botInlineMessageMediaAuto of BotInlineMessage (crc32 764cf810). */
final class TlBotInlineMessageBotInlineMessageMediaAuto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bot_inline_message_bot_inline_message_media_auto';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'invert_media' => 'bool',
        'message' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlBotInlineMessageBotInlineMessageMediaAutoEntities::class);
    }

    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
}
