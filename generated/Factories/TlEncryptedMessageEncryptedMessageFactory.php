<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEncryptedMessageEncryptedMessage (encryptedMessage). */
final class TlEncryptedMessageEncryptedMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedMessageEncryptedMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedMessageEncryptedMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'random_id' => 1001,
            'chat_id' => 2,
            'date' => 3,
            'bytes' => 'Ynl0ZXMtNA==',
            'file' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
