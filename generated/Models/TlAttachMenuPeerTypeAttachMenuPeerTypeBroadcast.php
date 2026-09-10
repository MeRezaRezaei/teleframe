<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for attachMenuPeerTypeBroadcast of AttachMenuPeerType (crc32 7bfbdefc). */
final class TlAttachMenuPeerTypeAttachMenuPeerTypeBroadcast extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_attach_menu_peer_type_attach_menu_peer_type_broadcast';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
