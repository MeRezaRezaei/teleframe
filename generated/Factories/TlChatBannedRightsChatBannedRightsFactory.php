<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatBannedRightsChatBannedRights (chatBannedRights). */
final class TlChatBannedRightsChatBannedRightsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRightsChatBannedRights> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRightsChatBannedRights::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'view_messages' => true,
            'send_messages' => true,
            'send_media' => true,
            'send_stickers' => true,
            'send_gifs' => true,
            'send_games' => true,
            'send_inline' => true,
            'embed_links' => true,
            'send_polls' => true,
            'change_info' => true,
            'invite_users' => true,
            'pin_messages' => true,
            'manage_topics' => true,
            'send_photos' => true,
            'send_videos' => true,
            'send_roundvideos' => true,
            'send_audios' => true,
            'send_voices' => true,
            'send_docs' => true,
            'send_plain' => true,
            'edit_rank' => true,
            'send_reactions' => true,
            'until_date' => 24,
        ];
    }
}
