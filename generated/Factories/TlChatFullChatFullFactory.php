<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatFullChatFull (chatFull). */
final class TlChatFullChatFullFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_set_username' => true,
            'has_scheduled' => true,
            'translations_disabled' => true,
            'tl_id' => 1005,
            'about' => 'about-6',
            'participants' => 1007,
            'chat_photo' => 1008,
            'notify_settings' => 1009,
            'exported_invite' => 1010,
            'pinned_msg_id' => 11,
            'folder_id' => 12,
            'call' => 1013,
            'ttl_period' => 14,
            'groupcall_default_join_as' => 1015,
            'theme_emoticon' => 'theme_emoticon-16',
            'requests_pending' => 17,
            'available_reactions' => 1018,
            'reactions_limit' => 19,
        ];
    }
}
