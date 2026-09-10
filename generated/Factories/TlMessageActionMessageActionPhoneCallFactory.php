<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionPhoneCall (messageActionPhoneCall). */
final class TlMessageActionMessageActionPhoneCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPhoneCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPhoneCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'video' => true,
            'call_id' => 1003,
            'reason' => 1004,
            'duration' => 5,
        ];
    }
}
