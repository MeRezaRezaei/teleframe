<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesFilterInputMessagesFilterPhoneCalls (inputMessagesFilterPhoneCalls). */
final class TlMessagesFilterInputMessagesFilterPhoneCallsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFilterInputMessagesFilterPhoneCalls> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFilterInputMessagesFilterPhoneCalls::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'missed' => true,
        ];
    }
}
