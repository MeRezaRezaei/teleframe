<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for pendingSuggestion of PendingSuggestion (crc32 e7e82e12). */
final class TlPendingSuggestionPendingSuggestion extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_pending_suggestion_pending_suggestion';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'suggestion' => 'string',
        'url' => 'string',
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'title');
    }
    public function description(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'description');
    }
}
