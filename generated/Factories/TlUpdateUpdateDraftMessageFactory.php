<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateDraftMessage (updateDraftMessage). */
final class TlUpdateUpdateDraftMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDraftMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDraftMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'top_msg_id' => 3,
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'draft' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
