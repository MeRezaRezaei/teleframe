<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoDataChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoDataDismissed_suggestions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoDataPending_suggestions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoDataUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPendingSuggestion;

/** Constructor model for help.promoData of help.PromoData (crc32 08a4d87a). */
final class TlHelpPromoDataPromoData extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_help_promo_data_promo_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'proxy' => 'bool',
        'expires' => 'int',
        'psa_type' => 'string',
        'psa_message' => 'string',
    ];

    public function pendingSuggestions(): HasMany
    {
        return $this->tlChild(TlHelpPromoDataPromoDataPending_suggestions::class);
    }
    public function dismissedSuggestions(): HasMany
    {
        return $this->tlChild(TlHelpPromoDataPromoDataDismissed_suggestions::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlHelpPromoDataPromoDataChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlHelpPromoDataPromoDataUsers::class);
    }

    public function customPendingSuggestion(): BelongsTo
    {
        return $this->belongsTo(TlPendingSuggestion::class, 'custom_pending_suggestion');
    }
}
