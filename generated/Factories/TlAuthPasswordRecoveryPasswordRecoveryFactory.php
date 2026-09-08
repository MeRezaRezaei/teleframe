<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthPasswordRecoveryPasswordRecovery (auth.passwordRecovery). */
final class TlAuthPasswordRecoveryPasswordRecoveryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthPasswordRecoveryPasswordRecovery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthPasswordRecoveryPasswordRecovery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email_pattern' => 'email_pattern-1',
        ];
    }
}
