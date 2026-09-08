<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallProtocolPhoneCallProtocol (phoneCallProtocol). */
final class TlPhoneCallProtocolPhoneCallProtocolFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallProtocolPhoneCallProtocol> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallProtocolPhoneCallProtocol::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'udp_p2p' => true,
            'udp_reflector' => true,
            'min_layer' => 4,
            'max_layer' => 5,
        ];
    }
}
