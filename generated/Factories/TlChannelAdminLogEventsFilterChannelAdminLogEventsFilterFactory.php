<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventsFilterChannelAdminLogEventsFilter (channelAdminLogEventsFilter). */
final class TlChannelAdminLogEventsFilterChannelAdminLogEventsFilterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventsFilterChannelAdminLogEventsFilter> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventsFilterChannelAdminLogEventsFilter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'join' => true,
            'leave' => true,
            'invite' => true,
            'ban' => true,
            'unban' => true,
            'kick' => true,
            'unkick' => true,
            'promote' => true,
            'demote' => true,
            'info' => true,
            'settings' => true,
            'pinned' => true,
            'edit' => true,
            'delete' => true,
            'group_call' => true,
            'invites' => true,
            'send' => true,
            'forums' => true,
            'sub_extend' => true,
            'edit_rank' => true,
        ];
    }
}
