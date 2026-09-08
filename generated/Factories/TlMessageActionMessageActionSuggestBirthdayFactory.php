<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionSuggestBirthday (messageActionSuggestBirthday). */
final class TlMessageActionMessageActionSuggestBirthdayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestBirthday> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestBirthday::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'birthday' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
