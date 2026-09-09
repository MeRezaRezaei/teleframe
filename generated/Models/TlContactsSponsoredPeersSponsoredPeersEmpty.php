<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for contacts.sponsoredPeersEmpty of contacts.SponsoredPeers (crc32 ea32b4b1). */
final class TlContactsSponsoredPeersSponsoredPeersEmpty extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contacts_sponsored_peers_sponsored_peers_empty';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
