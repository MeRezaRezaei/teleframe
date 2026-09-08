<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend (channelAdminLogEventActionParticipantSubExtend). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtendFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
