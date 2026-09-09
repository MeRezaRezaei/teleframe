<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionConferenceCallOther_participants;

/** Constructor model for messageActionConferenceCall of MessageAction (crc32 2ffe2f7a). */
final class TlMessageActionMessageActionConferenceCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_conference_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'missed' => 'bool',
        'active' => 'bool',
        'video' => 'bool',
        'call_id' => 'int',
        'duration' => 'int',
    ];

    public function otherParticipants(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionConferenceCallOther_participants::class);
    }
}
