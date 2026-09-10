<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionRequestedPeerPeers;

/** Constructor model for messageActionRequestedPeer of MessageAction (crc32 31518e9b). */
final class TlMessageActionMessageActionRequestedPeer extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_requested_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'button_id' => 'int',
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionRequestedPeerPeers::class);
    }
}
