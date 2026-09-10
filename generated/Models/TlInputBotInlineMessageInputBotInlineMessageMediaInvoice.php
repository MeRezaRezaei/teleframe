<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;

/** Constructor model for inputBotInlineMessageMediaInvoice of InputBotInlineMessage (crc32 d7e78225). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaInvoice extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__13ed224796c5';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title' => 'string',
        'description' => 'string',
        'payload' => 'string',
        'provider' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlInputWebDocument::class, 'photo');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TlInvoice::class, 'invoice');
    }
    public function providerData(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'provider_data');
    }
    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
}
