<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputEncryptedChatInputEncryptedChat (inputEncryptedChat). */
final class TlInputEncryptedChatInputEncryptedChatFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputEncryptedChatInputEncryptedChat> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputEncryptedChatInputEncryptedChat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_id' => 1,
            'access_hash' => 1002,
        ];
    }
}
