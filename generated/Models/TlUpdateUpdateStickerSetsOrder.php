<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStickerSetsOrderOrder;

/** Constructor model for updateStickerSetsOrder of Update (crc32 0bb2d201). */
final class TlUpdateUpdateStickerSetsOrder extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_sticker_sets_order';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'masks' => 'bool',
        'emojis' => 'bool',
    ];

    public function order(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateStickerSetsOrderOrder::class);
    }
}
