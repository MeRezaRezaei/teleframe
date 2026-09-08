<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPasskeyPasskey (passkey). */
final class TlPasskeyPasskeyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPasskeyPasskey> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPasskeyPasskey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_id' => 'id-2',
            'name' => 'name-3',
            'date' => 4,
            'software_emoji_id' => 1005,
            'last_usage_date' => 6,
        ];
    }
}
