<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEncryptedChatEncryptedChatWaiting (encryptedChatWaiting). */
final class TlEncryptedChatEncryptedChatWaitingFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatWaiting> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatWaiting::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1,
            'access_hash' => 1002,
            'date' => 3,
            'admin_id' => 1004,
            'participant_id' => 1005,
        ];
    }
}
