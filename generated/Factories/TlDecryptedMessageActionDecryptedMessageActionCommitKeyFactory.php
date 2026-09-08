<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageActionDecryptedMessageActionCommitKey (decryptedMessageActionCommitKey). */
final class TlDecryptedMessageActionDecryptedMessageActionCommitKeyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageActionDecryptedMessageActionCommitKey> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageActionDecryptedMessageActionCommitKey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'exchange_id' => 1001,
            'key_fingerprint' => 1002,
        ];
    }
}
