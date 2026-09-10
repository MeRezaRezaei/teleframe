<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageCaption;

/** Constructor model for pageBlockPhoto of PageBlock (crc32 1759c560). */
final class TlPageBlockPageBlockPhoto extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'spoiler' => 'bool',
        'photo_id' => 'int',
        'url' => 'string',
        'webpage_id' => 'int',
    ];

    public function caption(): BelongsTo
    {
        return $this->belongsTo(TlPageCaption::class, 'caption');
    }
}
