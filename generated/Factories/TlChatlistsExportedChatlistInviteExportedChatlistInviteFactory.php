<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatlistsExportedChatlistInviteExportedChatlistInvite (chatlists.exportedChatlistInvite). */
final class TlChatlistsExportedChatlistInviteExportedChatlistInviteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedChatlistInviteExportedChatlistInvite> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedChatlistInviteExportedChatlistInvite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'filter' => 1001,
            'invite' => 1002,
        ];
    }
}
