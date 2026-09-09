<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePoll;

/** Anchor model for TL type PollResults (spec §4.1). */
final class TlPollResults extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_poll_results';

    protected $guarded = [];

    public function results(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaPoll::class, 'results');
    }
    public function resultsUpdateMessagePoll(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateMessagePoll::class, 'results');
    }
}
