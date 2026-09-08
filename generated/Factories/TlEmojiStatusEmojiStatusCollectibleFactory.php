<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlEmojiStatusEmojiStatusCollectible (emojiStatusCollectible). */
final class TlEmojiStatusEmojiStatusCollectibleFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatusEmojiStatusCollectible> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatusEmojiStatusCollectible::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'collectible_id' => 1002,
            'document_id' => 1003,
            'title' => 'title-4',
            'slug' => 'slug-5',
            'pattern_document_id' => 1006,
            'center_color' => 7,
            'edge_color' => 8,
            'pattern_color' => 9,
            'text_color' => 10,
            'until' => 11,
        ];
    }
}
