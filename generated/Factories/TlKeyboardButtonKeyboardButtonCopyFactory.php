<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonKeyboardButtonCopy (keyboardButtonCopy). */
final class TlKeyboardButtonKeyboardButtonCopyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonCopy> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonCopy::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'style' => 1002,
            'text' => 'text-3',
            'copy_text' => 'copy_text-4',
        ];
    }
}
