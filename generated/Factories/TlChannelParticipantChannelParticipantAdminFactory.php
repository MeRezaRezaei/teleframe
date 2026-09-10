<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelParticipantChannelParticipantAdmin (channelParticipantAdmin). */
final class TlChannelParticipantChannelParticipantAdminFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantAdmin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantAdmin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_edit' => true,
            'self' => true,
            'user_id' => 1004,
            'inviter_id' => 1005,
            'promoted_by' => 1006,
            'date' => 7,
            'admin_rights' => 1008,
            'rank' => 'rank-9',
        ];
    }
}
