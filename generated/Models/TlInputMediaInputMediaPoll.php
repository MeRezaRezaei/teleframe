<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPollCorrect_answers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPollSolution_entities;

/** Constructor model for inputMediaPoll of InputMedia (crc32 883a4108). */
final class TlInputMediaInputMediaPoll extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_media_input_media_poll';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'poll' => 'string',
        'attached_media' => 'string',
        'solution' => 'string',
        'solution_media' => 'string',
    ];

    public function correctAnswers(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaPollCorrect_answers::class);
    }
    public function solutionEntities(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaPollSolution_entities::class);
    }
}
