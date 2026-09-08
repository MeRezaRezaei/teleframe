<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallPhoneCallAccepted (phoneCallAccepted). */
final class TlPhoneCallPhoneCallAcceptedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallAccepted> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallAccepted::class;

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
            'g_b' => 'Ynl0ZXMtOA==',
            'protocol' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
