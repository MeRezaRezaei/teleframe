<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlForumTopicForumTopic (forumTopic). */
final class TlForumTopicForumTopicFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopicForumTopic> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopicForumTopic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'my' => true,
            'closed' => true,
            'pinned' => true,
            'short' => true,
            'hidden' => true,
            'title_missing' => true,
            'tl_id' => 8,
            'date' => 9,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'title' => 'title-11',
            'icon_color' => 12,
            'icon_emoji_id' => 1013,
            'top_message' => 14,
            'read_inbox_max_id' => 15,
            'read_outbox_max_id' => 16,
            'unread_count' => 17,
            'unread_mentions_count' => 18,
            'unread_reactions_count' => 19,
            'unread_poll_votes_count' => 20,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'notify_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'draft' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
