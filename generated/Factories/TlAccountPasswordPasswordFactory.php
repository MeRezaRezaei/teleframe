<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountPasswordPassword (account.password). */
final class TlAccountPasswordPasswordFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordPassword> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordPassword::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_recovery' => true,
            'has_secure_values' => true,
            'has_password' => true,
            'current_algo' => 1005,
            'srp__b' => 'Ynl0ZXMtNg==',
            'srp_id' => 1007,
            'hint' => 'hint-8',
            'email_unconfirmed_pattern' => 'email_unconfirmed_pattern-9',
            'new_algo' => 1010,
            'new_secure_algo' => 1011,
            'secure_random' => 'Ynl0ZXMtMTI=',
            'pending_reset_date' => 13,
            'login_email_pattern' => 'login_email_pattern-14',
        ];
    }
}
