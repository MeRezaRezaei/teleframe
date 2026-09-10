<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineMessageInputBotInlineMessageMediaInvoice (inputBotInlineMessageMediaInvoice). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaInvoiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'title' => 'title-2',
            'description' => 'description-3',
            'photo' => 1004,
            'invoice' => 1005,
            'payload' => 'Ynl0ZXMtNg==',
            'provider' => 'provider-7',
            'provider_data' => 1008,
            'reply_markup' => 1009,
        ];
    }
}
