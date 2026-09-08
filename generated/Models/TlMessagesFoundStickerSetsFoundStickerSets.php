<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFoundStickerSetsFoundStickerSetsSets;

/** Constructor model for messages.foundStickerSets of messages.FoundStickerSets (crc32 8af09dd2). */
final class TlMessagesFoundStickerSetsFoundStickerSets extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_found_sticker_sets_found_sticker_sets';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function sets(): HasMany
    {
        return $this->tlChild(TlMessagesFoundStickerSetsFoundStickerSetsSets::class);
    }
}
