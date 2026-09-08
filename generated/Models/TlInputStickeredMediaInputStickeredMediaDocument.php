<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStickeredMediaDocument of InputStickeredMedia (crc32 0438865b). */
final class TlInputStickeredMediaInputStickeredMediaDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_stickered_media_input_stickered_media_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
    ];
}
