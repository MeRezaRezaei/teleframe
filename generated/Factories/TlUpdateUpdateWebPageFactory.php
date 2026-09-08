<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateWebPage (updateWebPage). */
final class TlUpdateUpdateWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'webpage' => (string) new \Symfony\Component\Uid\UuidV7(),
            'pts' => 2,
            'pts_count' => 3,
        ];
    }
}
