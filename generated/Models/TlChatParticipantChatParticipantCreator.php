<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatParticipantCreator of ChatParticipant (crc32 e1f867b8). */
final class TlChatParticipantChatParticipantCreator extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_participant_chat_participant_creator';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'user_id' => 'int',
        'rank' => 'string',
    ];
}
