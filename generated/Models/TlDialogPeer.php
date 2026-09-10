<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogPinned;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogUnreadMark;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateSavedDialogPinned;

/** Anchor model for TL type DialogPeer (spec §4.1). */
final class TlDialogPeer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_dialog_peer_dialog_peer';

    protected $guarded = [];

    public function peer(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateDialogPinned::class, 'peer');
    }
    public function peerUpdateDialogUnreadMark(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateDialogUnreadMark::class, 'peer');
    }
    public function peerUpdateSavedDialogPinned(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateSavedDialogPinned::class, 'peer');
    }
}
