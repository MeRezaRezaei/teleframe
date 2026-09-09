<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantGroupCallParticipant;

/** Anchor model for TL type GroupCallParticipantVideo (spec §4.1). */
final class TlGroupCallParticipantVideo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_group_call_participant_video';

    protected $guarded = [];

    public function presentation(): HasMany
    {
        return $this->hasMany(TlGroupCallParticipantGroupCallParticipant::class, 'presentation');
    }
    public function video(): HasMany
    {
        return $this->hasMany(TlGroupCallParticipantGroupCallParticipant::class, 'video');
    }
}
