<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionPollAppendAnswer (messageActionPollAppendAnswer). */
final class TlMessageActionMessageActionPollAppendAnswerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPollAppendAnswer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPollAppendAnswer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'answer' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
