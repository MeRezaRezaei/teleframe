<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUrlAuthResultUrlAuthResultRequest (urlAuthResultRequest). */
final class TlUrlAuthResultUrlAuthResultRequestFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUrlAuthResultUrlAuthResultRequest> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUrlAuthResultUrlAuthResultRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'request_write_access' => true,
            'request_phone_number' => true,
            'match_codes_first' => true,
            'is_app' => true,
            'bot' => 1006,
            'domain' => 'domain-7',
            'browser' => 'browser-8',
            'platform' => 'platform-9',
            'ip' => 'ip-10',
            'region' => 'region-11',
            'user_id_hint' => 1012,
            'verified_app_name' => 'verified_app_name-13',
        ];
    }
}
