<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSetClientDHParamsAnswerDhGenRetry (dh_gen_retry). */
final class TlSetClientDHParamsAnswerDhGenRetryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSetClientDHParamsAnswerDhGenRetry> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSetClientDHParamsAnswerDhGenRetry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nonce' => '99999999999999999999999999999999999999',
            'server_nonce' => '99999999999999999999999999999999999999',
            'new_nonce_hash2' => '99999999999999999999999999999999999999',
        ];
    }
}
