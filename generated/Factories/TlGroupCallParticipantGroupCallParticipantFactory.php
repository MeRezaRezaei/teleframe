<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallParticipantGroupCallParticipant (groupCallParticipant). */
final class TlGroupCallParticipantGroupCallParticipantFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantGroupCallParticipant> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantGroupCallParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'muted' => true,
            'left' => true,
            'can_self_unmute' => true,
            'just_joined' => true,
            'versioned' => true,
            'min' => true,
            'muted_by_you' => true,
            'volume_by_admin' => true,
            'self' => true,
            'video_joined' => true,
            'peer' => 1012,
            'date' => 13,
            'active_date' => 14,
            'source' => 15,
            'volume' => 16,
            'about' => 'about-17',
            'raise_hand_rating' => 1018,
            'video' => 1019,
            'presentation' => 1020,
            'paid_stars_total' => 1021,
        ];
    }
}
