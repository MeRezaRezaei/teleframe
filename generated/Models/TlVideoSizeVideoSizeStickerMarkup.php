<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSizeStickerMarkupBackground_colors;

/** Constructor model for videoSizeStickerMarkup of VideoSize (crc32 0da082fe). */
final class TlVideoSizeVideoSizeStickerMarkup extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_video_size_video_size_sticker_markup';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'sticker_id' => 'int',
    ];

    public function backgroundColors(): HasMany
    {
        return $this->tlChild(TlVideoSizeVideoSizeStickerMarkupBackground_colors::class);
    }

    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'stickerset');
    }
}
