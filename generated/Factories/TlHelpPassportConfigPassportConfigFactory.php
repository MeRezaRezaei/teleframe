<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpPassportConfigPassportConfig (help.passportConfig). */
final class TlHelpPassportConfigPassportConfigFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPassportConfigPassportConfig> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPassportConfigPassportConfig::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'hash' => 1,
            'countries_langs' => 1002,
        ];
    }
}
