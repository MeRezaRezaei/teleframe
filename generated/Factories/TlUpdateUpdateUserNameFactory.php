<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateUserName (updateUserName). */
final class TlUpdateUpdateUserNameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateUserName> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateUserName::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'first_name' => 'first_name-2',
            'last_name' => 'last_name-3',
        ];
    }
}
