<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInvitesExportedChatInvitesInvites;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInvitesExportedChatInvitesUsers;

/** Constructor model for messages.exportedChatInvites of messages.ExportedChatInvites (crc32 bdc62dcc). */
final class TlMessagesExportedChatInvitesExportedChatInvites extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_exported_chat_invites_exported_chat_invites';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function invites(): HasMany
    {
        return $this->tlChild(TlMessagesExportedChatInvitesExportedChatInvitesInvites::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesExportedChatInvitesExportedChatInvitesUsers::class);
    }
}
