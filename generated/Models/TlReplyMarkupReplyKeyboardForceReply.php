<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for replyKeyboardForceReply of ReplyMarkup (crc32 86b40b08). */
final class TlReplyMarkupReplyKeyboardForceReply extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_reply_markup_reply_keyboard_force_reply';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'single_use' => 'bool',
        'selective' => 'bool',
        'placeholder' => 'string',
    ];
}
