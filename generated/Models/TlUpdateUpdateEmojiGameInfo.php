<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesEmojiGameInfo;

/** Constructor model for updateEmojiGameInfo of Update (crc32 fb9c547a). */
final class TlUpdateUpdateEmojiGameInfo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_emoji_game_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function info(): BelongsTo
    {
        return $this->belongsTo(TlMessagesEmojiGameInfo::class, 'info');
    }
}
