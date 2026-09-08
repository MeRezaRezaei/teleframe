<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountSavedRingtoneSavedRingtoneConverted (account.savedRingtoneConverted). */
final class TlAccountSavedRingtoneSavedRingtoneConvertedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountSavedRingtoneSavedRingtoneConverted> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountSavedRingtoneSavedRingtoneConverted::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
