<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaContact (inputMediaContact). */
final class TlInputMediaInputMediaContactFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaContact> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaContact::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_number' => 'phone_number-1',
            'first_name' => 'first_name-2',
            'last_name' => 'last_name-3',
            'vcard' => 'vcard-4',
        ];
    }
}
