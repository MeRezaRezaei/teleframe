<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiGroupEmojiGroupEmoticons;

/** Constructor model for emojiGroup of EmojiGroup (crc32 7a9abda9). */
final class TlEmojiGroupEmojiGroup extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_emoji_group_emoji_group';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
        'icon_emoji_id' => 'int',
    ];

    public function emoticons(): HasMany
    {
        return $this->tlChild(TlEmojiGroupEmojiGroupEmoticons::class);
    }
}
