<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountEmojiStatusesEmojiStatusesStatuses;

/** Constructor model for account.emojiStatuses of account.EmojiStatuses (crc32 90c467d1). */
final class TlAccountEmojiStatusesEmojiStatuses extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_emoji_statuses_emoji_statuses';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function statuses(): HasMany
    {
        return $this->tlChild(TlAccountEmojiStatusesEmojiStatusesStatuses::class);
    }
}
