<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaInvoice (messageMediaInvoice). */
final class TlMessageMediaMessageMediaInvoiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaInvoice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaInvoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'shipping_address_requested' => true,
            'test' => true,
            'title' => 'title-4',
            'description' => 'description-5',
            'photo' => 1006,
            'receipt_msg_id' => 7,
            'currency' => 'currency-8',
            'total_amount' => 1009,
            'start_param' => 'start_param-10',
            'extended_media' => 1011,
        ];
    }
}
