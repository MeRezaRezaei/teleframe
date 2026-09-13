<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageReplyMarkupRowsButtonsPeerTypeUserAdminRight;

class TfBotInlineResultsSendMessageReplyMarkupRowsButtonsPeerTypeUserAdminRightFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageReplyMarkupRowsButtonsPeerTypeUserAdminRight::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'change_info' => fake()->boolean(),
            'post_messages' => fake()->boolean(),
            'edit_messages' => fake()->boolean(),
            'delete_messages' => fake()->boolean(),
            'ban_users' => fake()->boolean(),
            'invite_users' => fake()->boolean(),
            'pin_messages' => fake()->boolean(),
            'add_admins' => fake()->boolean(),
            'anonymous' => fake()->boolean(),
            'manage_call' => fake()->boolean(),
            'other' => fake()->boolean(),
            'manage_topics' => fake()->boolean(),
            'post_stories' => fake()->boolean(),
            'edit_stories' => fake()->boolean(),
            'delete_stories' => fake()->boolean(),
            'manage_direct_messages' => fake()->boolean(),
            'manage_ranks' => fake()->boolean(),
        ];
    }
}
