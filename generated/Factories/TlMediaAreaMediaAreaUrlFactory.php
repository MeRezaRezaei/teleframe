<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMediaAreaMediaAreaUrl (mediaAreaUrl). */
final class TlMediaAreaMediaAreaUrlFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaUrl> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaUrl::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'coordinates' => (string) new \Symfony\Component\Uid\UuidV7(),
            'url' => 'url-2',
        ];
    }
}
