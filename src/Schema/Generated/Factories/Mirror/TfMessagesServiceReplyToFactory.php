<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesServiceReplyTo;

class TfMessagesServiceReplyToFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesServiceReplyTo::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'reply_to_scheduled' => fake()->boolean(),
            'forum_topic' => fake()->boolean(),
            'quote' => fake()->boolean(),
            'reply_to_ephemeral' => fake()->boolean(),
            'reply_to_msg_id' => fake()->numberBetween(0, 2147483647),
            'reply_to_peer_id_type' => fake()->numberBetween(1, 3),
            'reply_to_peer_id_id' => fake()->unique()->randomNumber(8),
            'reply_to_top_id' => fake()->numberBetween(0, 2147483647),
            'quote_text' => fake()->word(),
            'quote_offset' => fake()->numberBetween(0, 2147483647),
            'todo_item_id' => fake()->numberBetween(0, 2147483647),
            'poll_option' => fake()->word(),
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'story_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
