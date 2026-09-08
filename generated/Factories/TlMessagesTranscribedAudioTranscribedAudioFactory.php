<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesTranscribedAudioTranscribedAudio (messages.transcribedAudio). */
final class TlMessagesTranscribedAudioTranscribedAudioFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesTranscribedAudioTranscribedAudio> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesTranscribedAudioTranscribedAudio::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'pending' => true,
            'transcription_id' => 1003,
            'text' => 'text-4',
            'trial_remains_num' => 5,
            'trial_remains_until_date' => 6,
        ];
    }
}
