<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerVotersPollAnswerVotersRecent_voters;

/** Constructor model for pollAnswerVoters of PollAnswerVoters (crc32 3645230a). */
final class TlPollAnswerVotersPollAnswerVoters extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_poll_answer_voters_poll_answer_voters';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'chosen' => 'bool',
        'correct' => 'bool',
        'option' => 'string',
        'voters' => 'int',
    ];

    public function recentVoters(): HasMany
    {
        return $this->tlChild(TlPollAnswerVotersPollAnswerVotersRecent_voters::class);
    }
}
