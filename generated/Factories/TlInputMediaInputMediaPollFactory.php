<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaPoll (inputMediaPoll). */
final class TlInputMediaInputMediaPollFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPoll> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPoll::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'poll' => (string) new \Symfony\Component\Uid\UuidV7(),
            'attached_media' => (string) new \Symfony\Component\Uid\UuidV7(),
            'solution' => 'solution-4',
            'solution_media' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
