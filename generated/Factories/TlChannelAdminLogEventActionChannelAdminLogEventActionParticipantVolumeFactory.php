<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolume (channelAdminLogEventActionParticipantVolume). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolumeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolume> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolume::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'participant' => 1001,
        ];
    }
}
