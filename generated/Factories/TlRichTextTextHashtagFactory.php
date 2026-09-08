<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRichTextTextHashtag (textHashtag). */
final class TlRichTextTextHashtagFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextHashtag> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextHashtag::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
