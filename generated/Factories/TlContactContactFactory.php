<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlContactContact (contact). */
final class TlContactContactFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactContact> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactContact::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'mutual' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
