<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageDecryptedMessageService (decryptedMessageService). */
final class TlDecryptedMessageDecryptedMessageServiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageDecryptedMessageService> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageDecryptedMessageService::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'random_id' => 1001,
            'action' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
