<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPhoto;

/** Constructor model for inputStickeredMediaPhoto of InputStickeredMedia (crc32 4a992157). */
final class TlInputStickeredMediaInputStickeredMediaPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_stickered_media_input_stickered_media_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function id(): BelongsTo
    {
        return $this->belongsTo(TlInputPhoto::class, 'tl_id');
    }
}
