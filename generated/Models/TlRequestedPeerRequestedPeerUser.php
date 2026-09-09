<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for requestedPeerUser of RequestedPeer (crc32 d62ff46a). */
final class TlRequestedPeerRequestedPeerUser extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_requested_peer_requested_peer_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'user_id' => 'int',
        'first_name' => 'string',
        'last_name' => 'string',
        'username' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'photo');
    }
}
