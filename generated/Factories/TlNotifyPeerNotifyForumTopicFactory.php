<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlNotifyPeerNotifyForumTopic (notifyForumTopic). */
final class TlNotifyPeerNotifyForumTopicFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlNotifyPeerNotifyForumTopic> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlNotifyPeerNotifyForumTopic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'top_msg_id' => 2,
        ];
    }
}
