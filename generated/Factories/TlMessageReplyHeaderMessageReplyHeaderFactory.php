<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageReplyHeaderMessageReplyHeader (messageReplyHeader). */
final class TlMessageReplyHeaderMessageReplyHeaderFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeaderMessageReplyHeader> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeaderMessageReplyHeader::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'reply_to_scheduled' => true,
            'forum_topic' => true,
            'quote' => true,
            'reply_to_ephemeral' => true,
            'reply_to_msg_id' => 6,
            'reply_to_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_from' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_media' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_to_top_id' => 10,
            'quote_text' => 'quote_text-11',
            'quote_offset' => 12,
            'todo_item_id' => 13,
            'poll_option' => 'Ynl0ZXMtMTQ=',
        ];
    }
}
