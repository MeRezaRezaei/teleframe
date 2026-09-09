<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;

/** Constructor model for updateChatParticipant of Update (crc32 d087663a). */
final class TlUpdateUpdateChatParticipant extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_chat_participant';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'chat_id' => 'int',
        'date' => 'int',
        'actor_id' => 'int',
        'user_id' => 'int',
        'qts' => 'int',
    ];

    public function prevParticipant(): BelongsTo
    {
        return $this->belongsTo(TlChatParticipant::class, 'prev_participant');
    }
    public function newParticipant(): BelongsTo
    {
        return $this->belongsTo(TlChatParticipant::class, 'new_participant');
    }
    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'invite');
    }
}
