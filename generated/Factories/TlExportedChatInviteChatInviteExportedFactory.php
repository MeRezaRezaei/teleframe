<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlExportedChatInviteChatInviteExported (chatInviteExported). */
final class TlExportedChatInviteChatInviteExportedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInviteChatInviteExported> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInviteChatInviteExported::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'revoked' => true,
            'permanent' => true,
            'request_needed' => true,
            'link' => 'link-5',
            'admin_id' => 1006,
            'date' => 7,
            'start_date' => 8,
            'expire_date' => 9,
            'usage_limit' => 10,
            'usage' => 11,
            'requested' => 12,
            'subscription_expired' => 13,
            'title' => 'title-14',
            'subscription_pricing' => 1015,
        ];
    }
}
