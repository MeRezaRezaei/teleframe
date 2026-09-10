<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkupReplyKeyboardMarkupRows;

/** Constructor model for replyKeyboardMarkup of ReplyMarkup (crc32 85dd99d1). */
final class TlReplyMarkupReplyKeyboardMarkup extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_reply_markup_reply_keyboard_markup';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'resize' => 'bool',
        'single_use' => 'bool',
        'selective' => 'bool',
        'persistent' => 'bool',
        'placeholder' => 'string',
    ];

    public function rows(): HasMany
    {
        return $this->tlChild(TlReplyMarkupReplyKeyboardMarkupRows::class);
    }
}
