<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPollAppendAnswer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPollDeleteAnswer;

/** Anchor model for TL type PollAnswer (spec §4.1). */
final class TlPollAnswer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_poll_answer';

    protected $guarded = [];

    public function answer(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPollAppendAnswer::class, 'answer');
    }
    public function answerMessageActionPollDeleteAnswer(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPollDeleteAnswer::class, 'answer');
    }
}
