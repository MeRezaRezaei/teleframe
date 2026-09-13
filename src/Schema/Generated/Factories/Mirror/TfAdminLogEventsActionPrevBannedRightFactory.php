<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionPrevBannedRight;

class TfAdminLogEventsActionPrevBannedRightFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionPrevBannedRight::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'view_messages' => fake()->boolean(),
            'send_messages' => fake()->boolean(),
            'send_media' => fake()->boolean(),
            'send_stickers' => fake()->boolean(),
            'send_gifs' => fake()->boolean(),
            'send_games' => fake()->boolean(),
            'send_inline' => fake()->boolean(),
            'embed_links' => fake()->boolean(),
            'send_polls' => fake()->boolean(),
            'change_info' => fake()->boolean(),
            'invite_users' => fake()->boolean(),
            'pin_messages' => fake()->boolean(),
            'manage_topics' => fake()->boolean(),
            'send_photos' => fake()->boolean(),
            'send_videos' => fake()->boolean(),
            'send_roundvideos' => fake()->boolean(),
            'send_audios' => fake()->boolean(),
            'send_voices' => fake()->boolean(),
            'send_docs' => fake()->boolean(),
            'send_plain' => fake()->boolean(),
            'edit_rank' => fake()->boolean(),
            'send_reactions' => fake()->boolean(),
            'until_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
