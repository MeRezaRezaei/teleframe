<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineResultInputBotInlineResult (inputBotInlineResult). */
final class TlInputBotInlineResultInputBotInlineResultFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResult> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResult::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_id' => 'id-2',
            'tl_type' => 'type-3',
            'title' => 'title-4',
            'description' => 'description-5',
            'url' => 'url-6',
            'thumb' => 1007,
            'content' => 1008,
            'send_message' => 1009,
        ];
    }
}
