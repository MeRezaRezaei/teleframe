<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesExportedChatInviteExportedChatInviteReplaced (messages.exportedChatInviteReplaced). */
final class TlMessagesExportedChatInviteExportedChatInviteReplacedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInviteReplaced> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesExportedChatInviteExportedChatInviteReplaced::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invite' => 1001,
            'new_invite' => 1002,
        ];
    }
}
