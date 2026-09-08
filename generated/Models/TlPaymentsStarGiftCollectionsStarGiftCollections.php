<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftCollectionsStarGiftCollectionsCollections;

/** Constructor model for payments.starGiftCollections of payments.StarGiftCollections (crc32 8a2932f3). */
final class TlPaymentsStarGiftCollectionsStarGiftCollections extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_star_gift_collections_star_gift_collections';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function collections(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftCollectionsStarGiftCollectionsCollections::class);
    }
}
