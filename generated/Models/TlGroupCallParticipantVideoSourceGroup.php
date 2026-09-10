<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type GroupCallParticipantVideoSourceGroup (spec §4.1). */
final class TlGroupCallParticipantVideoSourceGroup extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_group_call_participant_video_source_group__d4c024526fb4';

    protected $guarded = [];
}
