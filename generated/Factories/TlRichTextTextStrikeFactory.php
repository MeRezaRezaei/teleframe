<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRichTextTextStrike (textStrike). */
final class TlRichTextTextStrikeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextStrike> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextStrike::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
