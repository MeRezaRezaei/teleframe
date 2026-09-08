<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebPageWebPage (webPage). */
final class TlWebPageWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_large_media' => true,
            'video_cover_photo' => true,
            'tl_id' => 1004,
            'url' => 'url-5',
            'display_url' => 'display_url-6',
            'hash' => 7,
            'tl_type' => 'type-8',
            'site_name' => 'site_name-9',
            'title' => 'title-10',
            'description' => 'description-11',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'embed_url' => 'embed_url-13',
            'embed_type' => 'embed_type-14',
            'embed_width' => 15,
            'embed_height' => 16,
            'duration' => 17,
            'author' => 'author-18',
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'cached_page' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
