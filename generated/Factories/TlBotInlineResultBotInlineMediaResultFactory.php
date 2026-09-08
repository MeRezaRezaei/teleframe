<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineResultBotInlineMediaResult (botInlineMediaResult). */
final class TlBotInlineResultBotInlineMediaResultFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineMediaResult> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineMediaResult::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_id' => 'id-2',
            'tl_type' => 'type-3',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'title' => 'title-6',
            'description' => 'description-7',
            'send_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
