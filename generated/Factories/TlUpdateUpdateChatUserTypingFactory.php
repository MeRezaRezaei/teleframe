<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChatUserTyping (updateChatUserTyping). */
final class TlUpdateUpdateChatUserTypingFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatUserTyping> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatUserTyping::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_id' => 1001,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'action' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
