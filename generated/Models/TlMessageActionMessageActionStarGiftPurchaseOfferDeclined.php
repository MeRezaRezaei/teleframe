<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for messageActionStarGiftPurchaseOfferDeclined of MessageAction (crc32 73ada76b). */
final class TlMessageActionMessageActionStarGiftPurchaseOfferDeclined extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_star_gift_pu_8c254ffbf72a';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'expired' => 'bool',
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
    public function price(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'price');
    }
}
