<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSavedDialogMonoForumDialog (monoForumDialog). */
final class TlSavedDialogMonoForumDialogFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedDialogMonoForumDialog> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedDialogMonoForumDialog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'unread_mark' => true,
            'nopaid_messages_exception' => true,
            'peer' => 1004,
            'top_message' => 5,
            'read_inbox_max_id' => 6,
            'read_outbox_max_id' => 7,
            'unread_count' => 8,
            'unread_reactions_count' => 9,
            'draft' => 1010,
        ];
    }
}
