<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;

/** Constructor model for starGiftCollection of StarGiftCollection (crc32 9d6b13b0). */
final class TlStarGiftCollectionStarGiftCollection extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_collection_star_gift_collection';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'collection_id' => 'int',
        'title' => 'string',
        'gifts_count' => 'int',
        'hash' => 'int',
    ];

    public function icon(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'icon');
    }
}
