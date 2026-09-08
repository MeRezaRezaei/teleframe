<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaDocumentExternal (inputMediaDocumentExternal). */
final class TlInputMediaInputMediaDocumentExternalFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaDocumentExternal> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaDocumentExternal::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'spoiler' => true,
            'url' => 'url-3',
            'ttl_seconds' => 4,
            'video_cover' => (string) new \Symfony\Component\Uid\UuidV7(),
            'video_timestamp' => 6,
        ];
    }
}
