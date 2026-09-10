<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupParticipantsGroupParticipantsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupParticipantsGroupParticipantsParticipants;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupParticipantsGroupParticipantsUsers;

/** Constructor model for phone.groupParticipants of phone.GroupParticipants (crc32 f47751b6). */
final class TlPhoneGroupParticipantsGroupParticipants extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_group_participants_group_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
        'next_offset' => 'string',
        'version' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlPhoneGroupParticipantsGroupParticipantsParticipants::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPhoneGroupParticipantsGroupParticipantsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhoneGroupParticipantsGroupParticipantsUsers::class);
    }
}
