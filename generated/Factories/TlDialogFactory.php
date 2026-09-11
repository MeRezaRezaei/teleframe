<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDialog (domain: dialogs). */
final class TlDialogFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialog> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'pinned' => true,
            'unread_mark' => true,
            'view_forum_as_messages' => true,
            'peer' => 1005,
            'top_message' => 6,
            'read_inbox_max_id' => 7,
            'read_outbox_max_id' => 8,
            'unread_count' => 9,
            'unread_mentions_count' => 10,
            'unread_reactions_count' => 11,
            'unread_poll_votes_count' => 12,
            'notify_settings' => 1013,
            'pts' => 14,
            'draft' => 1015,
            'folder_id' => 16,
            'ttl_period' => 17,
        ];
    }
}
