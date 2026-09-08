<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesComposedMessageWithAIComposedMessageWithAI (messages.composedMessageWithAI). */
final class TlMessagesComposedMessageWithAIComposedMessageWithAIFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesComposedMessageWithAIComposedMessageWithAI> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesComposedMessageWithAIComposedMessageWithAI::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'result_text' => (string) new \Symfony\Component\Uid\UuidV7(),
            'diff_text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
