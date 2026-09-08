<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsMegagroupStatsMegagroupStats (stats.megagroupStats). */
final class TlStatsMegagroupStatsMegagroupStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'period' => (string) new \Symfony\Component\Uid\UuidV7(),
            'members' => (string) new \Symfony\Component\Uid\UuidV7(),
            'messages' => (string) new \Symfony\Component\Uid\UuidV7(),
            'viewers' => (string) new \Symfony\Component\Uid\UuidV7(),
            'posters' => (string) new \Symfony\Component\Uid\UuidV7(),
            'growth_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'members_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_members_by_source_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'languages_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'messages_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'actions_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'top_hours_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'weekdays_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
