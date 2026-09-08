<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMessage (message). */
final class TlMessageMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'out' => true,
            'mentioned' => true,
            'media_unread' => true,
            'silent' => true,
            'post' => true,
            'from_scheduled' => true,
            'legacy' => true,
            'edit_hide' => true,
            'pinned' => true,
            'noforwards' => true,
            'invert_media' => true,
            'flags2' => 13,
            'offline' => true,
            'video_processing_pending' => true,
            'paid_suggested_post_stars' => true,
            'paid_suggested_post_ton' => true,
            'tl_id' => 18,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'from_boosts_applied' => 20,
            'from_rank' => 'from_rank-21',
            'peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'fwd_from' => (string) new \Symfony\Component\Uid\UuidV7(),
            'via_bot_id' => 1025,
            'via_business_bot_id' => 1026,
            'guestchat_via_from' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_to' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 29,
            'message' => 'message-30',
            'media' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_markup' => (string) new \Symfony\Component\Uid\UuidV7(),
            'views' => 33,
            'forwards' => 34,
            'replies' => (string) new \Symfony\Component\Uid\UuidV7(),
            'edit_date' => 36,
            'post_author' => 'post_author-37',
            'grouped_id' => 1038,
            'reactions' => (string) new \Symfony\Component\Uid\UuidV7(),
            'ttl_period' => 40,
            'quick_reply_shortcut_id' => 41,
            'effect' => 1042,
            'factcheck' => (string) new \Symfony\Component\Uid\UuidV7(),
            'report_delivery_until_date' => 44,
            'paid_message_stars' => 1045,
            'suggested_post' => (string) new \Symfony\Component\Uid\UuidV7(),
            'schedule_repeat_period' => 47,
            'summary_from_language' => 'summary_from_language-48',
            'rich_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
