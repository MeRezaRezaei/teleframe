<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWallPaperWallPaperNoFile (wallPaperNoFile). */
final class TlWallPaperWallPaperNoFileFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaperNoFile> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaperNoFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'flags' => 2,
            'tl_default' => true,
            'dark' => true,
            'settings' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
