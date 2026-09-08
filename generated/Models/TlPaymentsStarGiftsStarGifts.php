<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftsStarGiftsGifts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftsStarGiftsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftsStarGiftsUsers;

/** Constructor model for payments.starGifts of payments.StarGifts (crc32 2ed82995). */
final class TlPaymentsStarGiftsStarGifts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_star_gifts_star_gifts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function gifts(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftsStarGiftsGifts::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftsStarGiftsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftsStarGiftsUsers::class);
    }
}
