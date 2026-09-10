<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNotifySettings;

/** Anchor model for TL type NotifyPeer (spec §4.1). */
final class TlNotifyPeer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_notify_peer_notify_broadcasts';

    protected $guarded = [];

    public function peer(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNotifySettings::class, 'peer');
    }
}
