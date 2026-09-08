<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageEntityMessageEntityBlockquote (messageEntityBlockquote). */
final class TlMessageEntityMessageEntityBlockquoteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityBlockquote> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityBlockquote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'collapsed' => true,
            'tl_offset' => 3,
            'length' => 4,
        ];
    }
}
