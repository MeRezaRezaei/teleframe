<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountEmailVerifiedEmailVerifiedLogin (account.emailVerifiedLogin). */
final class TlAccountEmailVerifiedEmailVerifiedLoginFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountEmailVerifiedEmailVerifiedLogin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountEmailVerifiedEmailVerifiedLogin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => 'email-1',
            'sent_code' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
