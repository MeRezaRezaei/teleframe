<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiKeywordEmojiKeywordEmoticons;

/** Constructor model for emojiKeyword of EmojiKeyword (crc32 d5b3b9f9). */
final class TlEmojiKeywordEmojiKeyword extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_emoji_keyword_emoji_keyword';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'keyword' => 'string',
    ];

    public function emoticons(): HasMany
    {
        return $this->tlChild(TlEmojiKeywordEmojiKeywordEmoticons::class);
    }
}
