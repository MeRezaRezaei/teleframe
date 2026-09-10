<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAiComposeToneAiComposeTone;

/** Anchor model for TL type AiComposeToneExample (spec §4.1). */
final class TlAiComposeToneExample extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_ai_compose_tone_example_ai_compose_tone_example';

    protected $guarded = [];

    public function exampleEnglish(): HasMany
    {
        return $this->hasMany(TlAiComposeToneAiComposeTone::class, 'example_english');
    }
}
