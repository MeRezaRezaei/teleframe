<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsSendAsPeersSendAsPeersChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsSendAsPeersSendAsPeersPeers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsSendAsPeersSendAsPeersUsers;

/** Constructor model for channels.sendAsPeers of channels.SendAsPeers (crc32 f496b0c6). */
final class TlChannelsSendAsPeersSendAsPeers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_send_as_peers_send_as_peers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlChannelsSendAsPeersSendAsPeersPeers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChannelsSendAsPeersSendAsPeersChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChannelsSendAsPeersSendAsPeersUsers::class);
    }
}
