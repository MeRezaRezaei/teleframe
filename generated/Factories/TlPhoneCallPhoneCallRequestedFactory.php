<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallPhoneCallRequested (phoneCallRequested). */
final class TlPhoneCallPhoneCallRequestedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallRequested> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallRequested::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'video' => true,
            'tl_id' => 1003,
            'access_hash' => 1004,
            'date' => 5,
            'admin_id' => 1006,
            'participant_id' => 1007,
            'g_a_hash' => 'Ynl0ZXMtOA==',
            'protocol' => 1009,
        ];
    }
}
