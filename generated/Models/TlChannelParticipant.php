<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for channel_participants (TL types: ChannelParticipant). */
final class TlChannelParticipant extends TlAnchorModel
{
    use AccountScoped, PeerResolution;

    protected $table = 'tf_channel_participants';

    protected $guarded = [];
}
