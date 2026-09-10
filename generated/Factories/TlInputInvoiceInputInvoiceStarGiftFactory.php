<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputInvoiceInputInvoiceStarGift (inputInvoiceStarGift). */
final class TlInputInvoiceInputInvoiceStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'hide_name' => true,
            'include_upgrade' => true,
            'peer' => 1004,
            'gift_id' => 1005,
            'message' => 1006,
        ];
    }
}
