<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlThemeTheme (theme). */
final class TlThemeThemeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeTheme> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeTheme::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'tl_default' => true,
            'for_chat' => true,
            'tl_id' => 1005,
            'access_hash' => 1006,
            'slug' => 'slug-7',
            'title' => 'title-8',
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'emoticon' => 'emoticon-10',
            'installs_count' => 11,
        ];
    }
}
