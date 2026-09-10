<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin (channelAdminLogEventActionParticipantToggleAdmin). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdminFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_participant' => 1001,
            'new_participant' => 1002,
        ];
    }
}
