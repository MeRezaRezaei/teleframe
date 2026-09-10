<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessageBusinessAwayMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessAwayMessageInputBusinessAwayMessage;

/** Anchor model for TL type BusinessAwayMessageSchedule (spec §4.1). */
final class TlBusinessAwayMessageSchedule extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_away_message_schedule_business_aw_c4687f6e65da';

    protected $guarded = [];

    public function schedule(): HasMany
    {
        return $this->hasMany(TlInputBusinessAwayMessageInputBusinessAwayMessage::class, 'schedule');
    }
    public function scheduleBusinessAwayMessage(): HasMany
    {
        return $this->hasMany(TlBusinessAwayMessageBusinessAwayMessage::class, 'schedule');
    }
}
