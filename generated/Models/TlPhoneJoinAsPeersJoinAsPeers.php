<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneJoinAsPeersJoinAsPeersChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneJoinAsPeersJoinAsPeersPeers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneJoinAsPeersJoinAsPeersUsers;

/** Constructor model for phone.joinAsPeers of phone.JoinAsPeers (crc32 afe5623f). */
final class TlPhoneJoinAsPeersJoinAsPeers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_join_as_peers_join_as_peers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlPhoneJoinAsPeersJoinAsPeersPeers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPhoneJoinAsPeersJoinAsPeersChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhoneJoinAsPeersJoinAsPeersUsers::class);
    }
}
