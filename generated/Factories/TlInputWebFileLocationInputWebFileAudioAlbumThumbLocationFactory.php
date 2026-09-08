<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation (inputWebFileAudioAlbumThumbLocation). */
final class TlInputWebFileLocationInputWebFileAudioAlbumThumbLocationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'small' => true,
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'title' => 'title-4',
            'performer' => 'performer-5',
        ];
    }
}
