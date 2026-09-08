<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneConnectionPhoneConnection (phoneConnection). */
final class TlPhoneConnectionPhoneConnectionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneConnectionPhoneConnection> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneConnectionPhoneConnection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tcp' => true,
            'tl_id' => 1003,
            'ip' => 'ip-4',
            'ipv6' => 'ipv6-5',
            'port' => 6,
            'peer_tag' => 'Ynl0ZXMtNw==',
        ];
    }
}
