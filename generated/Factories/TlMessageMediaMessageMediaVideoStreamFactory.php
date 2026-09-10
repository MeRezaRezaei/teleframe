<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaVideoStream (messageMediaVideoStream). */
final class TlMessageMediaMessageMediaVideoStreamFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaVideoStream> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaVideoStream::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rtmp_stream' => true,
            'call' => 1003,
        ];
    }
}
