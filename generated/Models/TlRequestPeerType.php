<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonRequestPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPeer;

/** Anchor model for TL type RequestPeerType (spec §4.1). */
final class TlRequestPeerType extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_request_peer_type';

    protected $guarded = [];

    public function peerType(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestPeer::class, 'peer_type');
    }
    public function peerTypeInputKeyboardButtonRequestPeer(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonRequestPeer::class, 'peer_type');
    }
}
