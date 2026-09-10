<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type phone.JoinAsPeers (spec §4.1). */
final class TlPhoneJoinAsPeers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_join_as_peers_join_as_peers';

    protected $guarded = [];
}
