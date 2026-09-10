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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPollCorrect_answers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPollSolution_entities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPoll;

/** Constructor model for inputMediaPoll of InputMedia (crc32 883a4108). */
final class TlInputMediaInputMediaPoll extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_poll';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'solution' => 'string',
    ];

    public function correctAnswers(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaPollCorrect_answers::class);
    }
    public function solutionEntities(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaPollSolution_entities::class);
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(TlPoll::class, 'poll');
    }
    public function attachedMedia(): BelongsTo
    {
        return $this->belongsTo(TlInputMedia::class, 'attached_media');
    }
    public function solutionMedia(): BelongsTo
    {
        return $this->belongsTo(TlInputMedia::class, 'solution_media');
    }
}
