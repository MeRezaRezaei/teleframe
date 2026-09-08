<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineResultBotInlineResult (botInlineResult). */
final class TlBotInlineResultBotInlineResultFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineResult> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineResult::class;

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
            'thumb' => (string) new \Symfony\Component\Uid\UuidV7(),
            'content' => (string) new \Symfony\Component\Uid\UuidV7(),
            'send_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
