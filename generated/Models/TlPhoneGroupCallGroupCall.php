<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCallChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCallParticipants;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCallUsers;

/** Constructor model for phone.groupCall of phone.GroupCall (crc32 9e727aad). */
final class TlPhoneGroupCallGroupCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_group_call_group_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'participants_next_offset' => 'string',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallGroupCallParticipants::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallGroupCallChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallGroupCallUsers::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlGroupCall::class, 'call');
    }
}
