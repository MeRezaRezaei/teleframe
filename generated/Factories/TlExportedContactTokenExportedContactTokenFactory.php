<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlExportedContactTokenExportedContactToken (exportedContactToken). */
final class TlExportedContactTokenExportedContactTokenFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedContactTokenExportedContactToken> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedContactTokenExportedContactToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'url' => 'url-1',
            'expires' => 2,
        ];
    }
}
