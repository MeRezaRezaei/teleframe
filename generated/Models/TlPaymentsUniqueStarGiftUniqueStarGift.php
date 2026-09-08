<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftUniqueStarGiftChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftUniqueStarGiftUsers;

/** Constructor model for payments.uniqueStarGift of payments.UniqueStarGift (crc32 416c56e8). */
final class TlPaymentsUniqueStarGiftUniqueStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_unique_star_gift_unique_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'gift' => 'string',
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsUniqueStarGiftUniqueStarGiftChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsUniqueStarGiftUniqueStarGiftUsers::class);
    }
}
