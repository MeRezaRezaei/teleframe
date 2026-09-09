<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsResolvedPeerResolvedPeerChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsResolvedPeerResolvedPeerUsers;

/** Constructor model for contacts.resolvedPeer of contacts.ResolvedPeer (crc32 7f077ad9). */
final class TlContactsResolvedPeerResolvedPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_contacts_resolved_peer_resolved_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlContactsResolvedPeerResolvedPeerChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsResolvedPeerResolvedPeerUsers::class);
    }
}
