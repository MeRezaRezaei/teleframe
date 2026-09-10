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
            'text' => 1002,
            'option' => 'Ynl0ZXMtMw==',
            'media' => 1004,
            'added_by' => 1005,
            'date' => 6,
        ];
    }
}
