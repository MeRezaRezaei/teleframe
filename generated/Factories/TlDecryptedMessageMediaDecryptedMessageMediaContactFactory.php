<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageMediaDecryptedMessageMediaContact (decryptedMessageMediaContact). */
final class TlDecryptedMessageMediaDecryptedMessageMediaContactFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaContact> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaContact::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_number' => 'phone_number-1',
            'first_name' => 'first_name-2',
            'last_name' => 'last_name-3',
            'user_id' => 4,
        ];
    }
}
