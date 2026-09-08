<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlReplyMarkupReplyKeyboardForceReply (replyKeyboardForceReply). */
final class TlReplyMarkupReplyKeyboardForceReplyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkupReplyKeyboardForceReply> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkupReplyKeyboardForceReply::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'single_use' => true,
            'selective' => true,
            'placeholder' => 'placeholder-4',
        ];
    }
}
