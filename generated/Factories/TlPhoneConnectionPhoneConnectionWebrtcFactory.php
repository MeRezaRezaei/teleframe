<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneConnectionPhoneConnectionWebrtc (phoneConnectionWebrtc). */
final class TlPhoneConnectionPhoneConnectionWebrtcFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneConnectionPhoneConnectionWebrtc> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneConnectionPhoneConnectionWebrtc::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'turn' => true,
            'stun' => true,
            'tl_id' => 1004,
            'ip' => 'ip-5',
            'ipv6' => 'ipv6-6',
            'port' => 7,
            'username' => 'username-8',
            'password' => 'password-9',
        ];
    }
}
