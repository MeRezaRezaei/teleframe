<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebDocumentWebDocument (webDocument). */
final class TlWebDocumentWebDocumentFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocumentWebDocument> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocumentWebDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'url' => 'url-1',
            'access_hash' => 1002,
            'tl_size' => 3,
            'mime_type' => 'mime_type-4',
        ];
    }
}
