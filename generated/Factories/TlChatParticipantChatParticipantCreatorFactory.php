<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatParticipantChatParticipantCreator (chatParticipantCreator). */
final class TlChatParticipantChatParticipantCreatorFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantChatParticipantCreator> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantChatParticipantCreator::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'user_id' => 1002,
            'rank' => 'rank-3',
        ];
    }
}
