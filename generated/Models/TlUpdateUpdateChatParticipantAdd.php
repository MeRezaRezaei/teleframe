<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for updateChatParticipantAdd of Update (crc32 3dda5451). */
final class TlUpdateUpdateChatParticipantAdd extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_chat_participant_add';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'chat_id' => 'int',
        'user_id' => 'int',
        'inviter_id' => 'int',
        'date' => 'int',
        'version' => 'int',
    ];
}
