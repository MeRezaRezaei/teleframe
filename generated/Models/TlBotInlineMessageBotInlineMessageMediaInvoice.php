<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botInlineMessageMediaInvoice of BotInlineMessage (crc32 354a9b09). */
final class TlBotInlineMessageBotInlineMessageMediaInvoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_inline_message_bot_inline_message_media_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'shipping_address_requested' => 'bool',
        'test' => 'bool',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'currency' => 'string',
        'total_amount' => 'int',
        'reply_markup' => 'string',
    ];
}
