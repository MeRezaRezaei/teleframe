<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesQuickReplyShortcutId;

class TfMessagesQuickReplyShortcutIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesQuickReplyShortcutId::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'quick_reply_shortcut_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
