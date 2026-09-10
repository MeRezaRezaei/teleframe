<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersDates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersPacks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesRecentStickersRecentStickersStickers;

/** Constructor model for messages.recentStickers of messages.RecentStickers (crc32 88d37c56). */
final class TlMessagesRecentStickersRecentStickers extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
