<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPhoneCallPhoneCallDiscarded (phoneCallDiscarded). */
final class TlPhoneCallPhoneCallDiscardedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallDiscarded> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallDiscarded::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'need_rating' => true,
            'need_debug' => true,
            'video' => true,
            'tl_id' => 1005,
            'reason' => (string) new \Symfony\Component\Uid\UuidV7(),
            'duration' => 7,
        ];
    }
}
