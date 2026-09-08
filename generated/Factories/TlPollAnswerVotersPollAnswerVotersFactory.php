<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPollAnswerVotersPollAnswerVoters (pollAnswerVoters). */
final class TlPollAnswerVotersPollAnswerVotersFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerVotersPollAnswerVoters> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerVotersPollAnswerVoters::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'chosen' => true,
            'correct' => true,
            'option' => 'Ynl0ZXMtNA==',
            'voters' => 5,
        ];
    }
}
