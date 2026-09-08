<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlJSONObjectValueJsonObjectValue (jsonObjectValue). */
final class TlJSONObjectValueJsonObjectValueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONObjectValueJsonObjectValue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONObjectValueJsonObjectValue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_key' => 'key-1',
            'tl_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
