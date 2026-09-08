<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEncryptedChatEncryptedChat (encryptedChat). */
final class TlEncryptedChatEncryptedChatFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChat> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1,
            'access_hash' => 1002,
            'date' => 3,
            'admin_id' => 1004,
            'participant_id' => 1005,
            'g_a_or_b' => 'Ynl0ZXMtNg==',
            'key_fingerprint' => 1007,
        ];
    }
}
