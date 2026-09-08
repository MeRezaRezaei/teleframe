<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaPoll (messageMediaPoll). */
final class TlMessageMediaMessageMediaPollFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPoll> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPoll::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'poll' => (string) new \Symfony\Component\Uid\UuidV7(),
            'results' => (string) new \Symfony\Component\Uid\UuidV7(),
            'attached_media' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
