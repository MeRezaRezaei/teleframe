<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the starsSubscription* ctors — table tf_stars_subscriptions.
 *
 * The required StarsSubscriptionPricing is a constructor discriminated union,
 * so the whole pricing object is carried in the pricing 1:1 child. peer_is_bot
 * is a union discriminator, not a data flag. id is the TL `string` id.
 *
 * @property int $account_id
 * @property string $id
 * @property int $until_date
 */
final class TfStarsSubscription extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_stars_subscriptions';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'peer_type' => 'int',
        'peer_id' => 'int',
        'until_date' => 'int',
        'canceled' => 'bool',
        'can_refulfill' => 'bool',
        'missing_balance' => 'bool',
        'bot_canceled' => 'bool',
    ];

    public function pricing(): HasOne
    {
        return $this->hasOne(TfStarsSubscriptionPricing::class, 'id', 'id');
    }

    public function chatInviteHash(): HasOne
    {
        return $this->hasOne(TfStarsSubscriptionChatInviteHash::class, 'id', 'id');
    }

    public function title(): HasOne
    {
        return $this->hasOne(TfStarsSubscriptionTitle::class, 'id', 'id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(TfStarsSubscriptionPhoto::class, 'id', 'id');
    }

    public function invoiceSlug(): HasOne
    {
        return $this->hasOne(TfStarsSubscriptionInvoiceSlug::class, 'id', 'id');
    }

    /**
     * The full composite key of this row, for writing a child fact.
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
