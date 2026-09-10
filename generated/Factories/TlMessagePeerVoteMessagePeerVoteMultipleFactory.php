<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagePeerVoteMessagePeerVoteMultiple (messagePeerVoteMultiple). */
final class TlMessagePeerVoteMessagePeerVoteMultipleFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagePeerVoteMessagePeerVoteMultiple> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagePeerVoteMessagePeerVoteMultiple::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'date' => 2,
        ];
    }
}
