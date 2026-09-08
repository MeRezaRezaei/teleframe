<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpSupportSupport (help.support). */
final class TlHelpSupportSupportFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpSupportSupport> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpSupportSupport::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_number' => 'phone_number-1',
            'tl_user' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
