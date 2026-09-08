<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkupReplyInlineMarkupRows;

/** Constructor model for replyInlineMarkup of ReplyMarkup (crc32 48a30254). */
final class TlReplyMarkupReplyInlineMarkup extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_reply_markup_reply_inline_markup';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function rows(): HasMany
    {
        return $this->tlChild(TlReplyMarkupReplyInlineMarkupRows::class);
    }
}
