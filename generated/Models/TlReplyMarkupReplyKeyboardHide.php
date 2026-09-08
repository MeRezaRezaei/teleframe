<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for replyKeyboardHide of ReplyMarkup (crc32 a03e5b85). */
final class TlReplyMarkupReplyKeyboardHide extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_reply_markup_reply_keyboard_hide';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'selective' => 'bool',
    ];
}
