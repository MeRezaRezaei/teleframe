<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionStarGift (messageActionStarGift). */
final class TlMessageActionMessageActionStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'name_hidden' => true,
            'saved' => true,
            'converted' => true,
            'upgraded' => true,
            'refunded' => true,
            'can_upgrade' => true,
            'prepaid_upgrade' => true,
            'upgrade_separate' => true,
            'auction_acquired' => true,
            'gift' => (string) new \Symfony\Component\Uid\UuidV7(),
            'message' => (string) new \Symfony\Component\Uid\UuidV7(),
            'convert_stars' => 1013,
            'upgrade_msg_id' => 14,
            'upgrade_stars' => 1015,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_id' => 1018,
            'prepaid_upgrade_hash' => 'prepaid_upgrade_hash-19',
            'gift_msg_id' => 20,
            'to_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'gift_num' => 22,
        ];
    }
}
