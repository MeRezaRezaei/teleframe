<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChatParticipants (updateChatParticipants). */
final class TlUpdateUpdateChatParticipantsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipants> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipants::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'participants' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
