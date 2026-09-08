<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlJSONValueJsonBool (jsonBool). */
final class TlJSONValueJsonBoolFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValueJsonBool> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValueJsonBool::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
