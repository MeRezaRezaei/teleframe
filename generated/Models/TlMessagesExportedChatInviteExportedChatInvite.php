<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInviteUsers;

/** Constructor model for messages.exportedChatInvite of messages.ExportedChatInvite (crc32 1871be50). */
final class TlMessagesExportedChatInviteExportedChatInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_exported_chat_invite_exported_chat_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'invite' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesExportedChatInviteExportedChatInviteUsers::class);
    }
}
