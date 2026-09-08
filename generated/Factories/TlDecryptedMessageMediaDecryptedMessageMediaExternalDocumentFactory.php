<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument (decryptedMessageMediaExternalDocument). */
final class TlDecryptedMessageMediaDecryptedMessageMediaExternalDocumentFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'access_hash' => 1002,
            'date' => 3,
            'mime_type' => 'mime_type-4',
            'tl_size' => 5,
            'thumb' => (string) new \Symfony\Component\Uid\UuidV7(),
            'dc_id' => 7,
        ];
    }
}
