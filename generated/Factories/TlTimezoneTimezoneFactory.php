<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlTimezoneTimezone (timezone). */
final class TlTimezoneTimezoneFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTimezoneTimezone> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTimezoneTimezone::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 'id-1',
            'name' => 'name-2',
            'utc_offset' => 3,
        ];
    }
}
