<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for attachMenuPeerTypeChat of AttachMenuPeerType (crc32 0509113f). */
final class TlAttachMenuPeerTypeAttachMenuPeerTypeChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_attach_menu_peer_type_attach_menu_peer_type_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
