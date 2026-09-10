<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputInvoiceInputInvoiceBusinessBotTransferStars (inputInvoiceBusinessBotTransferStars). */
final class TlInputInvoiceInputInvoiceBusinessBotTransferStarsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceBusinessBotTransferStars> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceBusinessBotTransferStars::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bot' => 1001,
            'stars' => 1002,
        ];
    }
}
