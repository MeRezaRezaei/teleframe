<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineResultInputBotInlineResultPhoto (inputBotInlineResultPhoto). */
final class TlInputBotInlineResultInputBotInlineResultPhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultPhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 'id-1',
            'tl_type' => 'type-2',
            'photo' => 1003,
            'send_message' => 1004,
        ];
    }
}
