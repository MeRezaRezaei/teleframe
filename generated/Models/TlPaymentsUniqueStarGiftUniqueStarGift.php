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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftUniqueStarGiftChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftUniqueStarGiftUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;

/** Constructor model for payments.uniqueStarGift of payments.UniqueStarGift (crc32 416c56e8). */
final class TlPaymentsUniqueStarGiftUniqueStarGift extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_unique_star_gift_unique_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsUniqueStarGiftUniqueStarGiftChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsUniqueStarGiftUniqueStarGiftUsers::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
}
