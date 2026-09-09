<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for updateChatParticipantAdmin of Update (crc32 d7ca61a2). */
final class TlUpdateUpdateChatParticipantAdmin extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_chat_participant_admin';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'chat_id' => 'int',
        'user_id' => 'int',
        'version' => 'int',
    ];

    public function isAdmin(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'is_admin');
    }
}
