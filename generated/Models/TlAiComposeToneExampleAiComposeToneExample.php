<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for aiComposeToneExample of AiComposeToneExample (crc32 f1d628ec). */
final class TlAiComposeToneExampleAiComposeToneExample extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_ai_compose_tone_example_ai_compose_tone_example';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function from(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'tl_from');
    }
    public function to(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'tl_to');
    }
}
