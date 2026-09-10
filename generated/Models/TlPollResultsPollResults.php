<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResultsRecent_voters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResultsResults;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResultsSolution_entities;

/** Constructor model for pollResults of PollResults (crc32 ba7bb15e). */
final class TlPollResultsPollResults extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_poll_results_poll_results';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'min' => 'bool',
        'has_unread_votes' => 'bool',
        'can_view_stats' => 'bool',
        'total_voters' => 'int',
        'solution' => 'string',
    ];

    public function results(): HasMany
    {
        return $this->tlChild(TlPollResultsPollResultsResults::class);
    }
    public function recentVoters(): HasMany
    {
        return $this->tlChild(TlPollResultsPollResultsRecent_voters::class);
    }
    public function solutionEntities(): HasMany
    {
        return $this->tlChild(TlPollResultsPollResultsSolution_entities::class);
    }

    public function solutionMedia(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'solution_media');
    }
}
