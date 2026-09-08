<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthorizationAuthorization (authorization). */
final class TlAuthorizationAuthorizationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthorizationAuthorization> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthorizationAuthorization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_current' => true,
            'official_app' => true,
            'password_pending' => true,
            'encrypted_requests_disabled' => true,
            'call_requests_disabled' => true,
            'unconfirmed' => true,
            'hash' => 1008,
            'device_model' => 'device_model-9',
            'platform' => 'platform-10',
            'system_version' => 'system_version-11',
            'api_id' => 12,
            'app_name' => 'app_name-13',
            'app_version' => 'app_version-14',
            'date_created' => 15,
            'date_active' => 16,
            'ip' => 'ip-17',
            'country' => 'country-18',
            'region' => 'region-19',
        ];
    }
}
