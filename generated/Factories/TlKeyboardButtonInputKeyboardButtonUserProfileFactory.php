<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonInputKeyboardButtonUserProfile (inputKeyboardButtonUserProfile). */
final class TlKeyboardButtonInputKeyboardButtonUserProfileFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUserProfile> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUserProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'style' => (string) new \Symfony\Component\Uid\UuidV7(),
            'text' => 'text-3',
            'user_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
