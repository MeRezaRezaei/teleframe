<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeRarity;

/** Constructor model for starGiftAttributePattern of StarGiftAttribute (crc32 4e7085ea). */
final class TlStarGiftAttributeStarGiftAttributePattern extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_star_gift_attribute_pattern';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'name' => 'string',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
    public function rarity(): BelongsTo
    {
        return $this->belongsTo(TlStarGiftAttributeRarity::class, 'rarity');
    }
}
