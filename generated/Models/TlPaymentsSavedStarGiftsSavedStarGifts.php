<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGiftsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGiftsGifts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGiftsUsers;

/** Constructor model for payments.savedStarGifts of payments.SavedStarGifts (crc32 95f389b1). */
final class TlPaymentsSavedStarGiftsSavedStarGifts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_saved_star_gifts_saved_star_gifts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function gifts(): HasMany
    {
        return $this->tlChild(TlPaymentsSavedStarGiftsSavedStarGiftsGifts::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsSavedStarGiftsSavedStarGiftsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsSavedStarGiftsSavedStarGiftsUsers::class);
    }

    public function chatNotificationsEnabled(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'chat_notifications_enabled');
    }
}
