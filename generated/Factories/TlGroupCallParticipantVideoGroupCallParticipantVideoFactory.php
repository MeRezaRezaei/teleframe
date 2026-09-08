<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallParticipantVideoGroupCallParticipantVideo (groupCallParticipantVideo). */
final class TlGroupCallParticipantVideoGroupCallParticipantVideoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantVideoGroupCallParticipantVideo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantVideoGroupCallParticipantVideo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'paused' => true,
            'endpoint' => 'endpoint-3',
            'audio_source' => 4,
        ];
    }
}
