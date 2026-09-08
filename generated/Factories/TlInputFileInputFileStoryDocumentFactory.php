<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputFileInputFileStoryDocument (inputFileStoryDocument). */
final class TlInputFileInputFileStoryDocumentFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileInputFileStoryDocument> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileInputFileStoryDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
