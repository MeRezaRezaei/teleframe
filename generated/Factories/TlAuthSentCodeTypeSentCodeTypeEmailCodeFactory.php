<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthSentCodeTypeSentCodeTypeEmailCode (auth.sentCodeTypeEmailCode). */
final class TlAuthSentCodeTypeSentCodeTypeEmailCodeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeTypeSentCodeTypeEmailCode> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeTypeSentCodeTypeEmailCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'apple_signin_allowed' => true,
            'google_signin_allowed' => true,
            'email_pattern' => 'email_pattern-4',
            'length' => 5,
            'reset_available_period' => 6,
            'reset_pending_date' => 7,
        ];
    }
}
