<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessBotRecipientsBusinessBotRecipients (businessBotRecipients). */
final class TlBusinessBotRecipientsBusinessBotRecipientsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRecipientsBusinessBotRecipients> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRecipientsBusinessBotRecipients::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'existing_chats' => true,
            'new_chats' => true,
            'contacts' => true,
            'non_contacts' => true,
            'exclude_selected' => true,
        ];
    }
}
