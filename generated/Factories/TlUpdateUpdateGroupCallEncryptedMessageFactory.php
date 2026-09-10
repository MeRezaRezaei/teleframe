<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateGroupCallEncryptedMessage (updateGroupCallEncryptedMessage). */
final class TlUpdateUpdateGroupCallEncryptedMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallEncryptedMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallEncryptedMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => 1001,
            'from_id' => 1002,
            'encrypted_message' => 'Ynl0ZXMtMw==',
        ];
    }
}
