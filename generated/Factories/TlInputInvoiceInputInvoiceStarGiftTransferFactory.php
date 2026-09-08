<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputInvoiceInputInvoiceStarGiftTransfer (inputInvoiceStarGiftTransfer). */
final class TlInputInvoiceInputInvoiceStarGiftTransferFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftTransfer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftTransfer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'stargift' => (string) new \Symfony\Component\Uid\UuidV7(),
            'to_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
