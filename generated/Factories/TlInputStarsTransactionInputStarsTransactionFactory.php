<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStarsTransactionInputStarsTransaction (inputStarsTransaction). */
final class TlInputStarsTransactionInputStarsTransactionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStarsTransactionInputStarsTransaction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStarsTransactionInputStarsTransaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'refund' => true,
            'tl_id' => 'id-3',
        ];
    }
}
