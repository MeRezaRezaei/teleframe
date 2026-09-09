<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;

/** Constructor model for updateBotChatInviteRequester of Update (crc32 7cb34d79). */
final class TlUpdateUpdateBotChatInviteRequester extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_bot_chat_invite_requester';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'date' => 'int',
        'user_id' => 'int',
        'about' => 'string',
        'qts' => 'int',
        'query_id' => 'int',
    ];

    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'invite');
    }
}
