<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param participants (table tl_phone_group_participants_group_participant_aa4634f2fc16). */
final class TlPhoneGroupParticipantsGroupParticipantsParticipants extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_group_participants_group_participant_aa4634f2fc16';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
