<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteDelete;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevoke;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInviteReplaced;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotChatInviteRequester;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipant;

/** Anchor model for TL type ExportedChatInvite (spec §4.1). */
final class TlExportedChatInvite extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_exported_chat_invite_chat_invite_exported';

    protected $guarded = [];

    public function exportedInvite(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'exported_invite');
    }
    public function exportedInviteChannelFull(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'exported_invite');
    }
    public function invite(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatParticipant::class, 'invite');
    }
    public function inviteChannelAdminLogEventActionExportedInviteDelete(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteDelete::class, 'invite');
    }
    public function inviteChannelAdminLogEventActionExportedInviteRevoke(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevoke::class, 'invite');
    }
    public function inviteChannelAdminLogEventActionParticipantJoinByInvite(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByInvite::class, 'invite');
    }
    public function inviteChannelAdminLogEventActionParticipantJoinByRequest(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest::class, 'invite');
    }
    public function inviteMessagesExportedChatInvite(): HasMany
    {
        return $this->hasMany(TlMessagesExportedChatInviteExportedChatInvite::class, 'invite');
    }
    public function inviteMessagesExportedChatInviteReplaced(): HasMany
    {
        return $this->hasMany(TlMessagesExportedChatInviteExportedChatInviteReplaced::class, 'invite');
    }
    public function inviteUpdateBotChatInviteRequester(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotChatInviteRequester::class, 'invite');
    }
    public function inviteUpdateChannelParticipant(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelParticipant::class, 'invite');
    }
    public function newInvite(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit::class, 'new_invite');
    }
    public function newInviteMessagesExportedChatInviteReplaced(): HasMany
    {
        return $this->hasMany(TlMessagesExportedChatInviteExportedChatInviteReplaced::class, 'new_invite');
    }
    public function prevInvite(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit::class, 'prev_invite');
    }
}
