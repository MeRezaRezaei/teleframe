<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaCoordinates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReaction;

/** Constructor model for mediaAreaSuggestedReaction of MediaArea (crc32 14455871). */
final class TlMediaAreaMediaAreaSuggestedReaction extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_media_area_media_area_suggested_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'dark' => 'bool',
        'flipped' => 'bool',
    ];

    public function coordinates(): BelongsTo
    {
        return $this->belongsTo(TlMediaAreaCoordinates::class, 'coordinates');
    }
    public function reaction(): BelongsTo
    {
        return $this->belongsTo(TlReaction::class, 'reaction');
    }
}
