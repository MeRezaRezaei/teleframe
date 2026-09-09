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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaDd29b020fdd6Users;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for inputStorePaymentPremiumGiftCode of InputStorePaymentPurpose (crc32 fb790393). */
final class TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'currency' => 'string',
        'amount' => 'int',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlInputStorePaymentPurposeInputStorePaDd29b020fdd6Users::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'message');
    }
}
