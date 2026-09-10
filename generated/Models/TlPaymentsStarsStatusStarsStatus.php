<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatusChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatusHistory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatusSubscriptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatusUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for payments.starsStatus of payments.StarsStatus (crc32 6c9ce8ed). */
final class TlPaymentsStarsStatusStarsStatus extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_stars_status_stars_status';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'subscriptions_next_offset' => 'string',
        'subscriptions_missing_balance' => 'int',
        'next_offset' => 'string',
    ];

    public function subscriptions(): HasMany
    {
        return $this->tlChild(TlPaymentsStarsStatusStarsStatusSubscriptions::class);
    }
    public function history(): HasMany
    {
        return $this->tlChild(TlPaymentsStarsStatusStarsStatusHistory::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsStarsStatusStarsStatusChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsStarsStatusStarsStatusUsers::class);
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'balance');
    }
}
