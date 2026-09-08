<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionSuggestProfilePhoto (messageActionSuggestProfilePhoto). */
final class TlMessageActionMessageActionSuggestProfilePhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestProfilePhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestProfilePhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
