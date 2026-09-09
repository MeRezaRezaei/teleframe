<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageCaption;

/** Constructor model for pageBlockMap of PageBlock (crc32 a44f3ef6). */
final class TlPageBlockPageBlockMap extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_map';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'zoom' => 'int',
        'w' => 'int',
        'h' => 'int',
    ];

    public function geo(): BelongsTo
    {
        return $this->belongsTo(TlGeoPoint::class, 'geo');
    }
    public function caption(): BelongsTo
    {
        return $this->belongsTo(TlPageCaption::class, 'caption');
    }
}
