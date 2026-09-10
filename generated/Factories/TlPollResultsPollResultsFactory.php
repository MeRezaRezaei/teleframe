<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPollResultsPollResults (pollResults). */
final class TlPollResultsPollResultsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResults> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResults::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'min' => true,
            'has_unread_votes' => true,
            'can_view_stats' => true,
            'total_voters' => 5,
            'solution' => 'solution-6',
            'solution_media' => 1007,
        ];
    }
}
