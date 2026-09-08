<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatInviteChatInvite (chatInvite). */
final class TlChatInviteChatInviteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInvite> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInvite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'channel' => true,
            'broadcast' => true,
            'public' => true,
            'megagroup' => true,
            'request_needed' => true,
            'verified' => true,
            'scam' => true,
            'fake' => true,
            'can_refulfill_subscription' => true,
            'title' => 'title-11',
            'about' => 'about-12',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'participants_count' => 14,
            'color' => 15,
            'subscription_pricing' => (string) new \Symfony\Component\Uid\UuidV7(),
            'subscription_form_id' => 1017,
            'bot_verification' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
