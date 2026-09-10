<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePoll;

/** Anchor model for TL type Poll (spec §4.1). */
final class TlPoll extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_poll_poll';

    protected $guarded = [];

    public function poll(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaPoll::class, 'poll');
    }
    public function pollMessageMediaPoll(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaPoll::class, 'poll');
    }
    public function pollUpdateMessagePoll(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateMessagePoll::class, 'poll');
    }
}
