<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputInvoiceInputInvoiceStarGiftAuctionBid (inputInvoiceStarGiftAuctionBid). */
final class TlInputInvoiceInputInvoiceStarGiftAuctionBidFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftAuctionBid> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftAuctionBid::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'hide_name' => true,
            'update_bid' => true,
            'peer' => 1004,
            'gift_id' => 1005,
            'bid_amount' => 1006,
            'message' => 1007,
        ];
    }
}
