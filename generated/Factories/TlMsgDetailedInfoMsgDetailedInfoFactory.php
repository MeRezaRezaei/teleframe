<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMsgDetailedInfoMsgDetailedInfo (msg_detailed_info). */
final class TlMsgDetailedInfoMsgDetailedInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgDetailedInfoMsgDetailedInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgDetailedInfoMsgDetailedInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'msg_id' => 1001,
            'answer_msg_id' => 1002,
            'bytes' => 3,
            'status' => 4,
        ];
    }
}
