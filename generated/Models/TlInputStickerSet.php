<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeCustomEmoji;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeSticker;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputStickerSetThumb;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSizeStickerMarkup;

/** Anchor model for TL type InputStickerSet (spec §4.1). */
final class TlInputStickerSet extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_sticker_set';

    protected $guarded = [];

    public function newStickerset(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet::class, 'new_stickerset');
    }
    public function newStickersetChannelAdminLogEventActionChangeEmojiStickerSet(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStickerSet::class, 'new_stickerset');
    }
    public function prevStickerset(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet::class, 'prev_stickerset');
    }
    public function prevStickersetChannelAdminLogEventActionChangeEmojiStickerSet(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStickerSet::class, 'prev_stickerset');
    }
    public function stickerset(): HasMany
    {
        return $this->hasMany(TlDocumentAttributeDocumentAttributeSticker::class, 'stickerset');
    }
    public function stickersetDocumentAttributeCustomEmoji(): HasMany
    {
        return $this->hasMany(TlDocumentAttributeDocumentAttributeCustomEmoji::class, 'stickerset');
    }
    public function stickersetInputStickerSetThumb(): HasMany
    {
        return $this->hasMany(TlInputFileLocationInputStickerSetThumb::class, 'stickerset');
    }
    public function stickersetVideoSizeStickerMarkup(): HasMany
    {
        return $this->hasMany(TlVideoSizeVideoSizeStickerMarkup::class, 'stickerset');
    }
}
