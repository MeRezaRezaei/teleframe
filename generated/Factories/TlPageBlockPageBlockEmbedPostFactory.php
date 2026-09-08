<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockEmbedPost (pageBlockEmbedPost). */
final class TlPageBlockPageBlockEmbedPostFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbedPost> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbedPost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'url' => 'url-1',
            'webpage_id' => 1002,
            'author_photo_id' => 1003,
            'author' => 'author-4',
            'date' => 5,
            'caption' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
