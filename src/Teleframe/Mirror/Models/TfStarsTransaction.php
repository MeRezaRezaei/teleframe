<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the starsTransaction ctor — table tf_stars_transactions.
 *
 * id is the TL `string` id (server-issued opaque handle). amount
 * (StarsAmount) and peer (StarsTransactionPeer) are required object facts
 * carried in the mandated 1:1 child tables (constructor-discriminated).
 *
 * @property int $account_id
 * @property string $id
 * @property int $date
 */
final class TfStarsTransaction extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'date' => 'int',
        'refund' => 'bool',
        'pending' => 'bool',
        'failed' => 'bool',
        'gift' => 'bool',
        'reaction' => 'bool',
        'stargift_upgrade' => 'bool',
        'business_transfer' => 'bool',
        'stargift_resale' => 'bool',
        'posts_search' => 'bool',
        'stargift_prepaid_upgrade' => 'bool',
        'stargift_drop_original_details' => 'bool',
        'phonegroup_message' => 'bool',
        'stargift_auction_bid' => 'bool',
        'offer' => 'bool',
    ];

    public function amount(): HasOne
    {
        return $this->hasOne(TfStarsTransactionAmount::class, 'id', 'id');
    }

    public function peer(): HasOne
    {
        return $this->hasOne(TfStarsTransactionPeer::class, 'id', 'id');
    }

    public function title(): HasOne
    {
        return $this->hasOne(TfStarsTransactionTitle::class, 'id', 'id');
    }

    public function description(): HasOne
    {
        return $this->hasOne(TfStarsTransactionDescription::class, 'id', 'id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(TfStarsTransactionPhoto::class, 'id', 'id');
    }

    public function transactionDate(): HasOne
    {
        return $this->hasOne(TfStarsTransactionTransactionDate::class, 'id', 'id');
    }

    public function transactionUrl(): HasOne
    {
        return $this->hasOne(TfStarsTransactionTransactionUrl::class, 'id', 'id');
    }

    public function botPayload(): HasOne
    {
        return $this->hasOne(TfStarsTransactionBotPayload::class, 'id', 'id');
    }

    public function msgId(): HasOne
    {
        return $this->hasOne(TfStarsTransactionMsgId::class, 'id', 'id');
    }

    /** 1:N — the Vector<MessageMedia> of extended_media; caller orders by slot. */
    public function extendedMedia(): HasMany
    {
        return $this->hasMany(TfStarsTransactionExtendedMedia::class, 'id', 'id');
    }

    public function subscriptionPeriod(): HasOne
    {
        return $this->hasOne(TfStarsTransactionSubscriptionPeriod::class, 'id', 'id');
    }

    public function giveawayPostId(): HasOne
    {
        return $this->hasOne(TfStarsTransactionGiveawayPostId::class, 'id', 'id');
    }

    public function stargift(): HasOne
    {
        return $this->hasOne(TfStarsTransactionStargift::class, 'id', 'id');
    }

    public function floodskipNumber(): HasOne
    {
        return $this->hasOne(TfStarsTransactionFloodskipNumber::class, 'id', 'id');
    }

    public function starrefCommissionPermille(): HasOne
    {
        return $this->hasOne(TfStarsTransactionStarrefCommissionPermille::class, 'id', 'id');
    }

    public function starrefPeer(): HasOne
    {
        return $this->hasOne(TfStarsTransactionStarrefPeer::class, 'id', 'id');
    }

    public function starrefAmount(): HasOne
    {
        return $this->hasOne(TfStarsTransactionStarrefAmount::class, 'id', 'id');
    }

    public function paidMessages(): HasOne
    {
        return $this->hasOne(TfStarsTransactionPaidMessages::class, 'id', 'id');
    }

    public function premiumGiftMonths(): HasOne
    {
        return $this->hasOne(TfStarsTransactionPremiumGiftMonths::class, 'id', 'id');
    }

    public function adsProceedsFromDate(): HasOne
    {
        return $this->hasOne(TfStarsTransactionAdsProceedsFromDate::class, 'id', 'id');
    }

    public function adsProceedsToDate(): HasOne
    {
        return $this->hasOne(TfStarsTransactionAdsProceedsToDate::class, 'id', 'id');
    }

    /**
     * The full composite key of this row, for writing a child fact:
     * `$tx->amount()->create([...$tx->childKey(), 'amount' => 100])`.
     *
     * @return array<string, int|string>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (string) $this->id,
        ];
    }
}
