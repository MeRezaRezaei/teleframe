<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputContactInputPhoneContact (inputPhoneContact). */
final class TlInputContactInputPhoneContactFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputContactInputPhoneContact> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputContactInputPhoneContact::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'client_id' => 1002,
            'phone' => 'phone-3',
            'first_name' => 'first_name-4',
            'last_name' => 'last_name-5',
            'note' => 1006,
        ];
    }
}
