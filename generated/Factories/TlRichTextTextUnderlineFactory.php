<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRichTextTextUnderline (textUnderline). */
final class TlRichTextTextUnderlineFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextUnderline> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextUnderline::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
