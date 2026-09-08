<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesExportedChatInviteExportedChatInvite (messages.exportedChatInvite). */
final class TlMessagesExportedChatInviteExportedChatInviteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInvite> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInvite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invite' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
