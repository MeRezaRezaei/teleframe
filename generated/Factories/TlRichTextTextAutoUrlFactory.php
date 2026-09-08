<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRichTextTextAutoUrl (textAutoUrl). */
final class TlRichTextTextAutoUrlFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAutoUrl> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAutoUrl::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
