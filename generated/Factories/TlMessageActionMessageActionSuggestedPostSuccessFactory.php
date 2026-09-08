<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionSuggestedPostSuccess (messageActionSuggestedPostSuccess). */
final class TlMessageActionMessageActionSuggestedPostSuccessFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostSuccess> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostSuccess::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'price' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
