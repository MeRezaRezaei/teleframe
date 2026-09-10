<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatChat (chat). */
final class TlChatChatFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'left' => true,
            'deactivated' => true,
            'call_active' => true,
            'call_not_empty' => true,
            'noforwards' => true,
            'tl_id' => 1008,
            'title' => 'title-9',
            'photo' => 1010,
            'participants_count' => 11,
            'date' => 12,
            'version' => 13,
            'migrated_to' => 1014,
            'admin_rights' => 1015,
            'default_banned_rights' => 1016,
        ];
    }
}
