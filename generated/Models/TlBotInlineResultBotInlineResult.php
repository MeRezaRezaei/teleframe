<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for botInlineResult of BotInlineResult (crc32 11965f3a). */
final class TlBotInlineResultBotInlineResult extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bot_inline_result_bot_inline_result';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'string',
        'tl_type' => 'string',
        'title' => 'string',
        'description' => 'string',
        'url' => 'string',
    ];

    public function thumb(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'thumb');
    }
    public function content(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'content');
    }
    public function sendMessage(): BelongsTo
    {
        return $this->belongsTo(TlBotInlineMessage::class, 'send_message');
    }
}
