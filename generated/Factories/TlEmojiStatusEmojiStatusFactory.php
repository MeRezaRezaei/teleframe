<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEmojiStatusEmojiStatus (emojiStatus). */
final class TlEmojiStatusEmojiStatusFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatusEmojiStatus> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatusEmojiStatus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'document_id' => 1002,
            'until' => 3,
        ];
    }
}
