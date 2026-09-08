<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions (account.passkeyRegistrationOptions). */
final class TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptionsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'options' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
