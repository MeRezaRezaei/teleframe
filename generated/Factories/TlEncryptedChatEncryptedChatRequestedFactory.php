<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEncryptedChatEncryptedChatRequested (encryptedChatRequested). */
final class TlEncryptedChatEncryptedChatRequestedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatRequested> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatRequested::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'folder_id' => 2,
            'tl_id' => 3,
            'access_hash' => 1004,
            'date' => 5,
            'admin_id' => 1006,
            'participant_id' => 1007,
            'g_a' => 'Ynl0ZXMtOA==',
        ];
    }
}
