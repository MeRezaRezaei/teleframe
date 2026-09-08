<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedInvitesExportedInvitesInvites;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedInvitesExportedInvitesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedInvitesExportedInvitesUsers;

/** Constructor model for chatlists.exportedInvites of chatlists.ExportedInvites (crc32 10ab6dc7). */
final class TlChatlistsExportedInvitesExportedInvites extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chatlists_exported_invites_exported_invites';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function invites(): HasMany
    {
        return $this->tlChild(TlChatlistsExportedInvitesExportedInvitesInvites::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChatlistsExportedInvitesExportedInvitesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChatlistsExportedInvitesExportedInvitesUsers::class);
    }
}
