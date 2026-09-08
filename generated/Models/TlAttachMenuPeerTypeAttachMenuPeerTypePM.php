<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for attachMenuPeerTypePM of AttachMenuPeerType (crc32 f146d31f). */
final class TlAttachMenuPeerTypeAttachMenuPeerTypePM extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_attach_menu_peer_type_attach_menu_peer_type_p_m';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
