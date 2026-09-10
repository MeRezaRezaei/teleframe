<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneGroupCallGroupCall (phone.groupCall). */
final class TlPhoneGroupCallGroupCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => 1001,
            'participants_next_offset' => 'participants_next_offset-2',
        ];
    }
}
