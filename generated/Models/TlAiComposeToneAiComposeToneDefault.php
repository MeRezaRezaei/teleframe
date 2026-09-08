<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for aiComposeToneDefault of AiComposeTone (crc32 9bad6414). */
final class TlAiComposeToneAiComposeToneDefault extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_ai_compose_tone_ai_compose_tone_default';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tone' => 'string',
        'emoji_id' => 'int',
        'title' => 'string',
    ];
}
