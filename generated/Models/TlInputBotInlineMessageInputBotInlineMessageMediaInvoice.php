<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputBotInlineMessageMediaInvoice of InputBotInlineMessage (crc32 d7e78225). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaInvoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__13ed224796c5';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'invoice' => 'string',
        'payload' => 'string',
        'provider' => 'string',
        'provider_data' => 'string',
        'reply_markup' => 'string',
    ];
}
