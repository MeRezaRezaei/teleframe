<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateDialogUnreadMark (updateDialogUnreadMark). */
final class TlUpdateUpdateDialogUnreadMarkFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogUnreadMark> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogUnreadMark::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'unread' => true,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
