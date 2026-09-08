<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputRichMessageInputRichMessage (inputRichMessage). */
final class TlInputRichMessageInputRichMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rtl' => true,
            'noautolink' => true,
        ];
    }
}
