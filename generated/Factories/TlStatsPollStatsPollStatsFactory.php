<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsPollStatsPollStats (stats.pollStats). */
final class TlStatsPollStatsPollStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPollStatsPollStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPollStatsPollStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'votes_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
