<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest (channelAdminLogEventActionParticipantJoinByRequest). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequestFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoinByRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'approved_by' => 1002,
        ];
    }
}
