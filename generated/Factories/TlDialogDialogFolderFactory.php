<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDialogDialogFolder (dialogFolder). */
final class TlDialogDialogFolderFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogDialogFolder> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogDialogFolder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'pinned' => true,
            'folder' => 1003,
            'peer' => 1004,
            'top_message' => 5,
            'unread_muted_peers_count' => 6,
            'unread_unmuted_peers_count' => 7,
            'unread_muted_messages_count' => 8,
            'unread_unmuted_messages_count' => 9,
        ];
    }
}
