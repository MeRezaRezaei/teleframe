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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChaD180f3d61600Users;

/** Constructor model for messages.exportedChatInviteReplaced of messages.ExportedChatInvite (crc32 222600ef). */
final class TlMessagesExportedChatInviteExportedChatInviteReplaced extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_exported_chat_invite_exported_cha_d180f3d61600';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesExportedChatInviteExportedChaD180f3d61600Users::class);
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'invite');
    }
    public function newInvite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'new_invite');
    }
}
