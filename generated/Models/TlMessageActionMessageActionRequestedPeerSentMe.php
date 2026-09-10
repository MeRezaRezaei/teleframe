<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionRequestedPeerSentMePeers;

/** Constructor model for messageActionRequestedPeerSentMe of MessageAction (crc32 93b31848). */
final class TlMessageActionMessageActionRequestedPeerSentMe extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_requested_peer_sent_me';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'button_id' => 'int',
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionRequestedPeerSentMePeers::class);
    }
}
