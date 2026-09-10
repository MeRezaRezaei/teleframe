<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputReplyToInputReplyToMessage (inputReplyToMessage). */
final class TlInputReplyToInputReplyToMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyToInputReplyToMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyToInputReplyToMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'reply_to_msg_id' => 2,
            'top_msg_id' => 3,
            'reply_to_peer_id' => 1004,
            'quote_text' => 'quote_text-5',
            'quote_offset' => 6,
            'monoforum_peer_id' => 1007,
            'todo_item_id' => 8,
            'poll_option' => 'Ynl0ZXMtOQ==',
        ];
    }
}
