<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesMessageEditDataMessageEditData (messages.messageEditData). */
final class TlMessagesMessageEditDataMessageEditDataFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessageEditDataMessageEditData> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessageEditDataMessageEditData::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'caption' => true,
        ];
    }
}
