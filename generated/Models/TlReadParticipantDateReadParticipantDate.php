<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for readParticipantDate of ReadParticipantDate (crc32 4a4ff172). */
final class TlReadParticipantDateReadParticipantDate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_read_participant_date_read_participant_date';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'date' => 'int',
    ];
}
