<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSet;

/** Constructor model for channelAdminLogEventActionChangeEmojiStickerSet of ChannelAdminLogEventAction (crc32 46d840ab). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStickerSet extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_2ab9a74c160a';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevStickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'prev_stickerset');
    }
    public function newStickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'new_stickerset');
    }
}
