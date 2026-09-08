<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePendingJoinRequestsRecent_requesters;

/** Constructor model for updatePendingJoinRequests of Update (crc32 7063c3db). */
final class TlUpdateUpdatePendingJoinRequests extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_pending_join_requests';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'requests_pending' => 'int',
    ];

    public function recentRequesters(): HasMany
    {
        return $this->tlChild(TlUpdateUpdatePendingJoinRequestsRecent_requesters::class);
    }
}
