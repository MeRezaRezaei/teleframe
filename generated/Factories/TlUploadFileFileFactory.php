<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUploadFileFile (upload.file). */
final class TlUploadFileFileFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUploadFileFile> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUploadFileFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_type' => (string) new \Symfony\Component\Uid\UuidV7(),
            'mtime' => 2,
            'bytes' => 'Ynl0ZXMtMw==',
        ];
    }
}
