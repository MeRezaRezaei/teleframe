<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageMediaDecryptedMessageMediaVideo (decryptedMessageMediaVideo). */
final class TlDecryptedMessageMediaDecryptedMessageMediaVideoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaVideo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaVideo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'thumb' => 'Ynl0ZXMtMQ==',
            'thumb_w' => 2,
            'thumb_h' => 3,
            'duration' => 4,
            'mime_type' => 'mime_type-5',
            'w' => 6,
            'h' => 7,
            'tl_size' => 8,
            'tl_key' => 'Ynl0ZXMtOQ==',
            'iv' => 'Ynl0ZXMtMTA=',
            'caption' => 'caption-11',
        ];
    }
}
