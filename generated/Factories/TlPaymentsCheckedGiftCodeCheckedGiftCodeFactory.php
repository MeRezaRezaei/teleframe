<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsCheckedGiftCodeCheckedGiftCode (payments.checkedGiftCode). */
final class TlPaymentsCheckedGiftCodeCheckedGiftCodeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsCheckedGiftCodeCheckedGiftCode> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsCheckedGiftCodeCheckedGiftCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'via_giveaway' => true,
            'from_id' => 1003,
            'giveaway_msg_id' => 4,
            'to_id' => 1005,
            'date' => 6,
            'days' => 7,
            'used_date' => 8,
        ];
    }
}
