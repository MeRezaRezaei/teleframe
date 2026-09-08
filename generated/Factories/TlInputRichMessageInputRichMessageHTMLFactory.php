<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputRichMessageInputRichMessageHTML (inputRichMessageHTML). */
final class TlInputRichMessageInputRichMessageHTMLFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageHTML> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageHTML::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rtl' => true,
            'noautolink' => true,
            'html' => 'html-4',
        ];
    }
}
