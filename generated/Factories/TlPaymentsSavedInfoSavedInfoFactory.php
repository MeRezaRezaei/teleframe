<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsSavedInfoSavedInfo (payments.savedInfo). */
final class TlPaymentsSavedInfoSavedInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedInfoSavedInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedInfoSavedInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_saved_credentials' => true,
            'saved_info' => 1003,
        ];
    }
}
