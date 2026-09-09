<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for attachMenuPeerTypeBotPM of AttachMenuPeerType (crc32 c32bfa1a). */
final class TlAttachMenuPeerTypeAttachMenuPeerTypeBotPM extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_attach_menu_peer_type_attach_menu_peer_type_bot_p_m';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
