<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRights;

/** Constructor model for updateChatDefaultBannedRights of Update (crc32 54c01850). */
final class TlUpdateUpdateChatDefaultBannedRights extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_chat_default_banned_rights';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'version' => 'int',
    ];

    public function defaultBannedRights(): BelongsTo
    {
        return $this->belongsTo(TlChatBannedRights::class, 'default_banned_rights');
    }
}
