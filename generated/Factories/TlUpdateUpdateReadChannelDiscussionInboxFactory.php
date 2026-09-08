<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateReadChannelDiscussionInbox (updateReadChannelDiscussionInbox). */
final class TlUpdateUpdateReadChannelDiscussionInboxFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadChannelDiscussionInbox> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadChannelDiscussionInbox::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'channel_id' => 1002,
            'top_msg_id' => 3,
            'read_max_id' => 4,
            'broadcast_id' => 1005,
            'broadcast_post' => 6,
        ];
    }
}
