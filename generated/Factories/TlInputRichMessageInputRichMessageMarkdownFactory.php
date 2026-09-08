<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputRichMessageInputRichMessageMarkdown (inputRichMessageMarkdown). */
final class TlInputRichMessageInputRichMessageMarkdownFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageMarkdown> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageMarkdown::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rtl' => true,
            'noautolink' => true,
            'markdown' => 'markdown-4',
        ];
    }
}
