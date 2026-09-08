<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateEncryptedMessagesRead (updateEncryptedMessagesRead). */
final class TlUpdateUpdateEncryptedMessagesReadFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEncryptedMessagesRead> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEncryptedMessagesRead::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_id' => 1,
            'max_date' => 2,
            'date' => 3,
        ];
    }
}
