<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaA2df1d4d4d93Additional_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaA2df1d4d4d93Countries_iso2;

/** Constructor model for inputStorePaymentPremiumGiveaway of InputStorePaymentPurpose (crc32 160544ca). */
final class TlInputStorePaymentPurposeInputStorePaymentPremiumGiveaway extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'only_new_subscribers' => 'bool',
        'winners_are_visible' => 'bool',
        'prize_description' => 'string',
        'random_id' => 'int',
        'until_date' => 'int',
        'currency' => 'string',
        'amount' => 'int',
    ];

    public function additionalPeers(): HasMany
    {
        return $this->tlChild(TlInputStorePaymentPurposeInputStorePaA2df1d4d4d93Additional_peers::class);
    }
    public function countriesIso2(): HasMany
    {
        return $this->tlChild(TlInputStorePaymentPurposeInputStorePaA2df1d4d4d93Countries_iso2::class);
    }
}
