<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallPhoneCallWaiting (phoneCallWaiting). */
final class TlPhoneCallPhoneCallWaitingFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallWaiting> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallWaiting::class;

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
            'protocol' => (string) new \Symfony\Component\Uid\UuidV7(),
            'receive_date' => 9,
        ];
    }
}
