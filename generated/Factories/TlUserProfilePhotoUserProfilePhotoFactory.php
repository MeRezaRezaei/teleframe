<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUserProfilePhotoUserProfilePhoto (userProfilePhoto). */
final class TlUserProfilePhotoUserProfilePhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserProfilePhotoUserProfilePhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserProfilePhotoUserProfilePhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_video' => true,
            'personal' => true,
            'photo_id' => 1004,
            'stripped_thumb' => 'Ynl0ZXMtNQ==',
            'dc_id' => 6,
        ];
    }
}
