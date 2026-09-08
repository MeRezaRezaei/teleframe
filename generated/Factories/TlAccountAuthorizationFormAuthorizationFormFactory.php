<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountAuthorizationFormAuthorizationForm (account.authorizationForm). */
final class TlAccountAuthorizationFormAuthorizationFormFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationForm> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationForm::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'privacy_policy_url' => 'privacy_policy_url-2',
        ];
    }
}
