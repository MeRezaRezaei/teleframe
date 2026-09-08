<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for contacts.topPeersDisabled of contacts.TopPeers (crc32 b52c939d). */
final class TlContactsTopPeersTopPeersDisabled extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_contacts_top_peers_top_peers_disabled';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
