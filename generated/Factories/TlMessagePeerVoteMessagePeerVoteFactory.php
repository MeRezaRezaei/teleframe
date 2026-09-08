<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagePeerVoteMessagePeerVote (messagePeerVote). */
final class TlMessagePeerVoteMessagePeerVoteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagePeerVoteMessagePeerVote> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagePeerVoteMessagePeerVote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'option' => 'Ynl0ZXMtMg==',
            'date' => 3,
        ];
    }
}
