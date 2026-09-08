<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesSentEncryptedMessageSentEncryptedFile (messages.sentEncryptedFile). */
final class TlMessagesSentEncryptedMessageSentEncryptedFileFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSentEncryptedMessageSentEncryptedFile> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSentEncryptedMessageSentEncryptedFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => 1,
            'file' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
