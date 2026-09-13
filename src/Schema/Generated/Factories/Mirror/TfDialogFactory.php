<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialog;

class TfDialogFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialog::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'top_message' => fake()->numberBetween(0, 2147483647),
            'read_inbox_max_id' => fake()->numberBetween(0, 2147483647),
            'read_outbox_max_id' => fake()->numberBetween(0, 2147483647),
            'unread_count' => fake()->numberBetween(0, 2147483647),
            'unread_mentions_count' => fake()->numberBetween(0, 2147483647),
            'unread_reactions_count' => fake()->numberBetween(0, 2147483647),
            'unread_poll_votes_count' => fake()->numberBetween(0, 2147483647),
            'pinned' => fake()->boolean(),
            'unread_mark' => fake()->boolean(),
            'view_forum_as_messages' => fake()->boolean(),
        ];
    }
}
