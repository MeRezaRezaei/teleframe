<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatAdminWithInvitesChatAdminWithInvites (chatAdminWithInvites). */
final class TlChatAdminWithInvitesChatAdminWithInvitesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminWithInvitesChatAdminWithInvites> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminWithInvitesChatAdminWithInvites::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'admin_id' => 1001,
            'invites_count' => 2,
            'revoked_invites_count' => 3,
        ];
    }
}
