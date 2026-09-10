<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineMessageBotInlineMessageMediaInvoice (botInlineMessageMediaInvoice). */
final class TlBotInlineMessageBotInlineMessageMediaInvoiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaInvoice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaInvoice::class;

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
            'currency' => 'currency-7',
            'total_amount' => 1008,
            'reply_markup' => 1009,
        ];
    }
}
