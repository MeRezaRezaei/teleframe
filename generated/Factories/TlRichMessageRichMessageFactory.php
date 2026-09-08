<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRichMessageRichMessage (richMessage). */
final class TlRichMessageRichMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichMessageRichMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichMessageRichMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rtl' => true,
            'part' => true,
        ];
    }
}
