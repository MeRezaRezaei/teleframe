<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputFileLocationInputGroupCallStream (inputGroupCallStream). */
final class TlInputFileLocationInputGroupCallStreamFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputGroupCallStream> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputGroupCallStream::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'call' => (string) new \Symfony\Component\Uid\UuidV7(),
            'time_ms' => 1003,
            'scale' => 4,
            'video_channel' => 5,
            'video_quality' => 6,
        ];
    }
}
