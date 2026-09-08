<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute (channelAdminLogEventActionParticipantMute). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMuteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'participant' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
