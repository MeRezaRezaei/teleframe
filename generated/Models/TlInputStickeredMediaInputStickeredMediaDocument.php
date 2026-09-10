<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDocument;

/** Constructor model for inputStickeredMediaDocument of InputStickeredMedia (crc32 0438865b). */
final class TlInputStickeredMediaInputStickeredMediaDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_stickered_media_input_stickered_media_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function id(): BelongsTo
    {
        return $this->belongsTo(TlInputDocument::class, 'tl_id');
    }
}
