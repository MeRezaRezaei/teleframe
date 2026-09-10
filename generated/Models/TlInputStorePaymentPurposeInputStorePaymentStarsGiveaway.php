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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaAb10defc70e9Additional_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaAb10defc70e9Countries_iso2;

/** Constructor model for inputStorePaymentStarsGiveaway of InputStorePaymentPurpose (crc32 751f08fa). */
final class TlInputStorePaymentPurposeInputStorePaymentStarsGiveaway extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_ab10defc70e9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'only_new_subscribers' => 'bool',
        'winners_are_visible' => 'bool',
        'stars' => 'int',
        'prize_description' => 'string',
        'random_id' => 'int',
        'until_date' => 'int',
        'currency' => 'string',
        'amount' => 'int',
        'users' => 'int',
    ];

    public function additionalPeers(): HasMany
    {
        return $this->tlChild(TlInputStorePaymentPurposeInputStorePaAb10defc70e9Additional_peers::class);
    }
    public function countriesIso2(): HasMany
    {
        return $this->tlChild(TlInputStorePaymentPurposeInputStorePaAb10defc70e9Countries_iso2::class);
    }
}
