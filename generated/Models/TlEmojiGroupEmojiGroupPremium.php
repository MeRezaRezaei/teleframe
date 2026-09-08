<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for emojiGroupPremium of EmojiGroup (crc32 093bcf34). */
final class TlEmojiGroupEmojiGroupPremium extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_emoji_group_emoji_group_premium';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
        'icon_emoji_id' => 'int',
    ];
}
