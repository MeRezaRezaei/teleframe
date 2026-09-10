<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsTransactionStarsTransaction (starsTransaction). */
final class TlStarsTransactionStarsTransactionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'refund' => true,
            'pending' => true,
            'failed' => true,
            'gift' => true,
            'reaction' => true,
            'stargift_upgrade' => true,
            'business_transfer' => true,
            'stargift_resale' => true,
            'posts_search' => true,
            'stargift_prepaid_upgrade' => true,
            'stargift_drop_original_details' => true,
            'phonegroup_message' => true,
            'stargift_auction_bid' => true,
            'offer' => true,
            'tl_id' => 'id-16',
            'amount' => 1017,
            'date' => 18,
            'peer' => 1019,
            'title' => 'title-20',
            'description' => 'description-21',
            'photo' => 1022,
            'transaction_date' => 23,
            'transaction_url' => 'transaction_url-24',
            'bot_payload' => 'Ynl0ZXMtMjU=',
            'msg_id' => 26,
            'subscription_period' => 27,
            'giveaway_post_id' => 28,
            'stargift' => 1029,
            'floodskip_number' => 30,
            'starref_commission_permille' => 31,
            'starref_peer' => 1032,
            'starref_amount' => 1033,
            'paid_messages' => 34,
            'premium_gift_months' => 35,
            'ads_proceeds_from_date' => 36,
            'ads_proceeds_to_date' => 37,
        ];
    }
}
