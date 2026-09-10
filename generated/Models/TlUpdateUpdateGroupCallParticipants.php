<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallParticipantsParticipants;

/** Constructor model for updateGroupCallParticipants of Update (crc32 f2ebdb4e). */
final class TlUpdateUpdateGroupCallParticipants extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_group_call_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'version' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateGroupCallParticipantsParticipants::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
}
