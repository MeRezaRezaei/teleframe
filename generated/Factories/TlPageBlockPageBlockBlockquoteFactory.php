<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockBlockquote (pageBlockBlockquote). */
final class TlPageBlockPageBlockBlockquoteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquote> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
            'caption' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
