<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallPhoneCall (phoneCall). */
final class TlPhoneCallPhoneCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'p2p_allowed' => true,
            'video' => true,
            'conference_supported' => true,
            'tl_id' => 1005,
            'access_hash' => 1006,
            'date' => 7,
            'admin_id' => 1008,
            'participant_id' => 1009,
            'g_a_or_b' => 'Ynl0ZXMtMTA=',
            'key_fingerprint' => 1011,
            'protocol' => (string) new \Symfony\Component\Uid\UuidV7(),
            'start_date' => 13,
            'custom_parameters' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
