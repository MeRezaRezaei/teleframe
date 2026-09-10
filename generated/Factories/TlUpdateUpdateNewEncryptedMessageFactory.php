<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateNewEncryptedMessage (updateNewEncryptedMessage). */
final class TlUpdateUpdateNewEncryptedMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewEncryptedMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewEncryptedMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => 1001,
            'qts' => 2,
        ];
    }
}
