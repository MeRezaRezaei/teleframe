<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantsChatParticipantsParticipants;

/** Constructor model for chatParticipants of ChatParticipants (crc32 3cbc93f8). */
final class TlChatParticipantsChatParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_participants_chat_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'chat_id' => 'int',
        'version' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlChatParticipantsChatParticipantsParticipants::class);
    }
}
