<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputGroupCallStream;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGroupCallScheduled;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionInviteToGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaVideoStream;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDeleteGroupCallMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallChainBlocks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallEncryptedMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallParticipants;

/** Anchor model for TL type InputGroupCall (spec §4.1). */
final class TlInputGroupCall extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_group_call';

    protected $guarded = [];

    public function call(): HasMany
    {
        return $this->hasMany(TlInputFileLocationInputGroupCallStream::class, 'call');
    }
    public function callChannelAdminLogEventActionDiscardGroupCall(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCall::class, 'call');
    }
    public function callChannelAdminLogEventActionStartGroupCall(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCall::class, 'call');
    }
    public function callChannelFull(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'call');
    }
    public function callChatFull(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'call');
    }
    public function callMessageActionGroupCall(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionGroupCall::class, 'call');
    }
    public function callMessageActionGroupCallScheduled(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionGroupCallScheduled::class, 'call');
    }
    public function callMessageActionInviteToGroupCall(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionInviteToGroupCall::class, 'call');
    }
    public function callMessageMediaVideoStream(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaVideoStream::class, 'call');
    }
    public function callUpdateDeleteGroupCallMessages(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateDeleteGroupCallMessages::class, 'call');
    }
    public function callUpdateGroupCallChainBlocks(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCallChainBlocks::class, 'call');
    }
    public function callUpdateGroupCallEncryptedMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCallEncryptedMessage::class, 'call');
    }
    public function callUpdateGroupCallMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCallMessage::class, 'call');
    }
    public function callUpdateGroupCallParticipants(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCallParticipants::class, 'call');
    }
}
