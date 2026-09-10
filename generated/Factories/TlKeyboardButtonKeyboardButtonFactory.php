<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonKeyboardButton (keyboardButton). */
final class TlKeyboardButtonKeyboardButtonFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButton> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButton::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'style' => 1002,
            'text' => 'text-3',
        ];
    }
}
