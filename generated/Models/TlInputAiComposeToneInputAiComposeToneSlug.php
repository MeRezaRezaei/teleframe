<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputAiComposeToneSlug of InputAiComposeTone (crc32 1fa01357). */
final class TlInputAiComposeToneInputAiComposeToneSlug extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_ai_compose_tone_input_ai_compose_tone_slug';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'slug' => 'string',
    ];
}
