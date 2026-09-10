<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAttributeStarGiftAttributeOriginalDetails (starGiftAttributeOriginalDetails). */
final class TlStarGiftAttributeStarGiftAttributeOriginalDetailsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeOriginalDetails> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeOriginalDetails::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'sender_id' => 1002,
            'recipient_id' => 1003,
            'date' => 4,
            'message' => 1005,
        ];
    }
}
