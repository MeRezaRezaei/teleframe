<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockCollage (pageBlockCollage). */
final class TlPageBlockPageBlockCollageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockCollage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockCollage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'caption' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
