<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryAlbumStoryAlbum (storyAlbum). */
final class TlStoryAlbumStoryAlbumFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryAlbumStoryAlbum> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryAlbumStoryAlbum::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'album_id' => 2,
            'title' => 'title-3',
            'icon_photo' => 1004,
            'icon_video' => 1005,
        ];
    }
}
