<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPollAnswerPollAnswer (pollAnswer). */
final class TlPollAnswerPollAnswerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerPollAnswer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerPollAnswer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
            'option' => 'Ynl0ZXMtMw==',
            'media' => (string) new \Symfony\Component\Uid\UuidV7(),
            'added_by' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 6,
        ];
    }
}
