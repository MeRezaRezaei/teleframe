<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param sources (table tl_group_call_participant_video_source_group__3aa33ccb5d6b). */
final class TlGroupCallParticipantVideoSourceGroupD4c024526fb4Sources extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_group_call_participant_video_source_group__3aa33ccb5d6b';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
