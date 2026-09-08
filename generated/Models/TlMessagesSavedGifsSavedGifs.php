<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedGifsSavedGifsGifs;

/** Constructor model for messages.savedGifs of messages.SavedGifs (crc32 84a02a0d). */
final class TlMessagesSavedGifsSavedGifs extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_saved_gifs_saved_gifs';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function gifs(): HasMany
    {
        return $this->tlChild(TlMessagesSavedGifsSavedGifsGifs::class);
    }
}
