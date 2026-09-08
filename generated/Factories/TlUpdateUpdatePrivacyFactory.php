<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdatePrivacy (updatePrivacy). */
final class TlUpdateUpdatePrivacyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePrivacy> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePrivacy::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_key' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
