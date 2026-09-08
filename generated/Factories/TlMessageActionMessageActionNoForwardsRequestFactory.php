<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionNoForwardsRequest (messageActionNoForwardsRequest). */
final class TlMessageActionMessageActionNoForwardsRequestFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionNoForwardsRequest> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionNoForwardsRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'expired' => true,
            'prev_value' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
