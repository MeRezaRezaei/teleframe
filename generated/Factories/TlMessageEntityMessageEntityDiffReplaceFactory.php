<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageEntityMessageEntityDiffReplace (messageEntityDiffReplace). */
final class TlMessageEntityMessageEntityDiffReplaceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityDiffReplace> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityDiffReplace::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_offset' => 1,
            'length' => 2,
            'old_text' => 'old_text-3',
        ];
    }
}
