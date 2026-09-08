<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWallPaperWallPaper (wallPaper). */
final class TlWallPaperWallPaperFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaper> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaper::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'flags' => 2,
            'creator' => true,
            'tl_default' => true,
            'pattern' => true,
            'dark' => true,
            'access_hash' => 1007,
            'slug' => 'slug-8',
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'settings' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
