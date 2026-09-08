<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthLoginTokenLoginTokenSuccess (auth.loginTokenSuccess). */
final class TlAuthLoginTokenLoginTokenSuccessFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthLoginTokenLoginTokenSuccess> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthLoginTokenLoginTokenSuccess::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_authorization' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
