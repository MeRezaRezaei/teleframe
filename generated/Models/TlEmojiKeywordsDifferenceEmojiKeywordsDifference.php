<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiKeywordsDifferenceEmojiKeywordsDifferenceKeywords;

/** Constructor model for emojiKeywordsDifference of EmojiKeywordsDifference (crc32 5cc761bd). */
final class TlEmojiKeywordsDifferenceEmojiKeywordsDifference extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_emoji_keywords_difference_emoji_keywords_difference';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'lang_code' => 'string',
        'from_version' => 'int',
        'version' => 'int',
    ];

    public function keywords(): HasMany
    {
        return $this->tlChild(TlEmojiKeywordsDifferenceEmojiKeywordsDifferenceKeywords::class);
    }
}
