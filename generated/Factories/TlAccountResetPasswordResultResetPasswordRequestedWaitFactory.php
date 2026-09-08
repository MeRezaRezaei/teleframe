<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountResetPasswordResultResetPasswordRequestedWait (account.resetPasswordRequestedWait). */
final class TlAccountResetPasswordResultResetPasswordRequestedWaitFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountResetPasswordResultResetPasswordRequestedWait> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountResetPasswordResultResetPasswordRequestedWait::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'until_date' => 1,
        ];
    }
}
