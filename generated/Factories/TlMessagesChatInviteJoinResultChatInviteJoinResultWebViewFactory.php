<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesChatInviteJoinResultChatInviteJoinResultWebView (messages.chatInviteJoinResultWebView). */
final class TlMessagesChatInviteJoinResultChatInviteJoinResultWebViewFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteJoinResultChatInviteJoinResultWebView> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteJoinResultChatInviteJoinResultWebView::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bot_id' => 1001,
            'webview' => 1002,
        ];
    }
}
