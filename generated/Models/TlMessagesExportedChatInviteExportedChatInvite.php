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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInviteUsers;

/** Constructor model for messages.exportedChatInvite of messages.ExportedChatInvite (crc32 1871be50). */
final class TlMessagesExportedChatInviteExportedChatInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_exported_chat_invite_exported_chat_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesExportedChatInviteExportedChatInviteUsers::class);
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'invite');
    }
}
