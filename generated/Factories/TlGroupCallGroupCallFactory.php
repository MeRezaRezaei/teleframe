<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallGroupCall (groupCall). */
final class TlGroupCallGroupCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallGroupCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallGroupCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'join_muted' => true,
            'can_change_join_muted' => true,
            'join_date_asc' => true,
            'schedule_start_subscribed' => true,
            'can_start_video' => true,
            'record_video_active' => true,
            'rtmp_stream' => true,
            'listeners_hidden' => true,
            'conference' => true,
            'creator' => true,
            'messages_enabled' => true,
            'can_change_messages_enabled' => true,
            'min' => true,
            'tl_id' => 1015,
            'access_hash' => 1016,
            'participants_count' => 17,
            'title' => 'title-18',
            'stream_dc_id' => 19,
            'record_start_date' => 20,
            'schedule_date' => 21,
            'unmuted_video_count' => 22,
            'unmuted_video_limit' => 23,
            'version' => 24,
            'invite_link' => 'invite_link-25',
            'send_paid_messages_stars' => 1026,
            'default_send_as' => 1027,
        ];
    }
}
