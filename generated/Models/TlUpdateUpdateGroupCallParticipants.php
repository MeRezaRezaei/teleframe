<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallParticipantsParticipants;

/** Constructor model for updateGroupCallParticipants of Update (crc32 f2ebdb4e). */
final class TlUpdateUpdateGroupCallParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_group_call_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'call' => 'string',
        'version' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateGroupCallParticipantsParticipants::class);
    }
}
