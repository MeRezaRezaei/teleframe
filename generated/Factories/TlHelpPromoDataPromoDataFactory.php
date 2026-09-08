<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpPromoDataPromoData (help.promoData). */
final class TlHelpPromoDataPromoDataFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoData> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoData::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'proxy' => true,
            'expires' => 3,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'psa_type' => 'psa_type-5',
            'psa_message' => 'psa_message-6',
            'custom_pending_suggestion' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
