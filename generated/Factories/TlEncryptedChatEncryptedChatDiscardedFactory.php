<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEncryptedChatEncryptedChatDiscarded (encryptedChatDiscarded). */
final class TlEncryptedChatEncryptedChatDiscardedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatDiscarded> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedChatEncryptedChatDiscarded::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'history_deleted' => true,
            'tl_id' => 3,
        ];
    }
}
