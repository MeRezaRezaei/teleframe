<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersPacks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersStickers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersDates;

/** Constructor model for messages.recentStickers of messages.RecentStickers (crc32 88d37c56). */
final class TlMessagesRecentStickersRecentStickers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_recent_stickers_recent_stickers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function packs(): HasMany
    {
        return $this->tlChild(TlMessagesRecentStickersRecentStickersPacks::class);
    }
    public function stickers(): HasMany
    {
        return $this->tlChild(TlMessagesRecentStickersRecentStickersStickers::class);
    }
    public function dates(): HasMany
    {
        return $this->tlChild(TlMessagesRecentStickersRecentStickersDates::class);
    }
}
