<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUser;

class TfUserFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUser::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'self' => fake()->boolean(),
            'contact' => fake()->boolean(),
            'mutual_contact' => fake()->boolean(),
            'deleted' => fake()->boolean(),
            'bot' => fake()->boolean(),
            'bot_chat_history' => fake()->boolean(),
            'bot_nochats' => fake()->boolean(),
            'verified' => fake()->boolean(),
            'restricted' => fake()->boolean(),
            'min' => fake()->boolean(),
            'bot_inline_geo' => fake()->boolean(),
            'support' => fake()->boolean(),
            'scam' => fake()->boolean(),
            'apply_min_photo' => fake()->boolean(),
            'fake' => fake()->boolean(),
            'bot_attach_menu' => fake()->boolean(),
            'premium' => fake()->boolean(),
            'attach_menu_enabled' => fake()->boolean(),
            'bot_can_edit' => fake()->boolean(),
            'close_friend' => fake()->boolean(),
            'stories_hidden' => fake()->boolean(),
            'stories_unavailable' => fake()->boolean(),
            'contact_require_premium' => fake()->boolean(),
            'bot_business' => fake()->boolean(),
            'bot_has_main_app' => fake()->boolean(),
            'bot_forum_view' => fake()->boolean(),
            'bot_forum_can_manage_topics' => fake()->boolean(),
            'bot_can_manage_bots' => fake()->boolean(),
            'bot_guestchat' => fake()->boolean(),
            'bot_guard' => fake()->boolean(),
        ];
    }
}
