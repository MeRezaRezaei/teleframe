<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputFileLocationInputPeerPhotoFileLocation (inputPeerPhotoFileLocation). */
final class TlInputFileLocationInputPeerPhotoFileLocationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputPeerPhotoFileLocation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputPeerPhotoFileLocation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'big' => true,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'photo_id' => 1004,
        ];
    }
}
