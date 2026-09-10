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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollPollAnswers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollPollCountries_iso2;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for poll of Poll (crc32 966e2dbf). */
final class TlPollPoll extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_poll_poll';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'flags' => 'int',
        'closed' => 'bool',
        'public_voters' => 'bool',
        'multiple_choice' => 'bool',
        'quiz' => 'bool',
        'open_answers' => 'bool',
        'revoting_disabled' => 'bool',
        'shuffle_answers' => 'bool',
        'hide_results_until_close' => 'bool',
        'creator' => 'bool',
        'subscribers_only' => 'bool',
        'close_period' => 'int',
        'close_date' => 'int',
        'hash' => 'int',
    ];

    public function answers(): HasMany
    {
        return $this->tlChild(TlPollPollAnswers::class);
    }
    public function countriesIso2(): HasMany
    {
        return $this->tlChild(TlPollPollCountries_iso2::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'question');
    }
}
