<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebAuthorizationWebAuthorization (webAuthorization). */
final class TlWebAuthorizationWebAuthorizationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebAuthorizationWebAuthorization> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebAuthorizationWebAuthorization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'hash' => 1001,
            'bot_id' => 1002,
            'domain' => 'domain-3',
            'browser' => 'browser-4',
            'platform' => 'platform-5',
            'date_created' => 6,
            'date_active' => 7,
            'ip' => 'ip-8',
            'region' => 'region-9',
        ];
    }
}
