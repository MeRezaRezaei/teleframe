<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputUserInputUserFromMessage (inputUserFromMessage). */
final class TlInputUserInputUserFromMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUserInputUserFromMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUserInputUserFromMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'msg_id' => 2,
            'user_id' => 1003,
        ];
    }
}
