<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelWebPage (updateChannelWebPage). */
final class TlUpdateUpdateChannelWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'webpage' => 1002,
            'pts' => 3,
            'pts_count' => 4,
        ];
    }
}
