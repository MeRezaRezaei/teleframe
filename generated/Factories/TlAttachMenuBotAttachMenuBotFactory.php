<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAttachMenuBotAttachMenuBot (attachMenuBot). */
final class TlAttachMenuBotAttachMenuBotFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotAttachMenuBot> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotAttachMenuBot::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'inactive' => true,
            'has_settings' => true,
            'request_write_access' => true,
            'show_in_attach_menu' => true,
            'show_in_side_menu' => true,
            'side_menu_disclaimer_needed' => true,
            'bot_id' => 1008,
            'short_name' => 'short_name-9',
        ];
    }
}
